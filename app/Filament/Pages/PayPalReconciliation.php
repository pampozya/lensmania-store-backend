<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;

final class PayPalReconciliation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'PayPal Reconciliation';

    protected static ?string $navigationGroup = 'System & Security';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'PayPal Reconciliation';

    protected static string $view = 'filament.pages.paypal-reconciliation';

    public function getRows(): array
    {
        return Order::query()
            ->with(['user', 'product'])
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'customer' => $order->user?->email ?? 'Unknown',
                'product' => $order->product_slug ?: $order->product_name,
                'amount' => '$' . number_format($this->amount($order), 2) . ' ' . ($order->currency ?: 'USD'),
                'status' => $order->status . ' / ' . $order->api_status,
                'paypal_order_id' => $order->paypal_order_id ?: '-',
                'paypal_capture_id' => $order->paypal_capture_id ?: ($order->paypal_payment_id ?: '-'),
                'issues' => $this->issues($order),
            ])
            ->all();
    }

    private function issues(Order $order): array
    {
        $issues = [];
        $looksPaid = $order->status === 'paid' || in_array($order->api_status, ['paid', 'fulfilled'], true);

        if ($looksPaid && ! $order->paypal_capture_id && ! $order->paypal_payment_id) {
            $issues[] = 'Missing PayPal capture/payment id';
        }

        if ($order->api_status === 'fulfilled' && $order->status !== 'paid') {
            $issues[] = 'Fulfilled before payment status is paid';
        }

        if (($order->currency ?: 'USD') !== 'USD') {
            $issues[] = 'Non-USD currency';
        }

        if ($order->amount_cents !== null && $order->amount_usd !== null && abs(($order->amount_cents / 100) - $order->amount_usd) > 0.01) {
            $issues[] = 'Amount cents/USD mismatch';
        }

        return $issues;
    }

    private function amount(Order $order): float
    {
        if ($order->amount_usd !== null) {
            return (float) $order->amount_usd;
        }

        return $order->amount_cents !== null ? ((int) $order->amount_cents) / 100 : 0;
    }
}
