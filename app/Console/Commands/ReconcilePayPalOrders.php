<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePayPalOrders extends Command
{
    protected $signature = 'orders:reconcile-paypal {--stale-minutes=20}';

    protected $description = 'Reconcile created PayPal orders against PayPal every 15 minutes.';

    public function handle(CheckoutService $checkoutService): int
    {
        $staleMinutes = max(1, (int) $this->option('stale-minutes'));
        $checked = 0;
        $captured = 0;
        $failed = 0;

        Order::query()
            ->whereNotNull('paypal_order_id')
            ->whereNull('paypal_capture_id')
            ->where('api_status', 'pending')
            ->where('created_at', '<=', now()->subMinutes($staleMinutes))
            ->orderBy('id')
            ->chunkById(50, function ($orders) use ($checkoutService, &$checked, &$captured, &$failed) {
                foreach ($orders as $order) {
                    $checked++;

                    try {
                        $checkoutService->captureReturnedPayPalOrder((string) $order->paypal_order_id);

                        if ($order->fresh()?->paypal_capture_id) {
                            $captured++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::info('paypal_reconcile_pending_or_failed', [
                            'order_id' => $order->id,
                            'paypal_order_id' => $order->paypal_order_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Checked {$checked} PayPal orders; captured {$captured}; pending/failed {$failed}.");

        return self::SUCCESS;
    }
}
