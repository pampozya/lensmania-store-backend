<?php

namespace App\Services;

use App\Models\BundleItem;
use App\Models\Entitlement;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\PriceQuote;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FulfillmentService
{
    public function __construct(private AuditService $auditService)
    {
    }

    public function fulfill($order, array $capture, bool $fromReturn = false): void
    {
        if (! $order instanceof Order) {
            return;
        }

        $captureStatus = strtoupper((string) ($capture['status'] ?? 'COMPLETED'));
        if ($captureStatus !== 'COMPLETED') {
            return;
        }

        $this->fulfillOrderWithCapture($order, $capture, $fromReturn);
    }

    public function fulfillIfNeededFromWebhookOrReturn($order, bool $fromReturn): void
    {
        if (! $order instanceof Order) {
            return;
        }

        $capture = [
            'id' => (string) ($order->paypal_capture_id ?: 'CAPTURE-' . $order->id),
            'status' => 'COMPLETED',
            'amount' => [
                'value' => number_format((float) $order->amount_cents / 100, 2, '.', ''),
                'currency_code' => $order->currency,
            ],
            'invoice_id' => $order->paypal_order_id,
            'custom_id' => $order->paypal_order_id,
        ];

        $this->fulfillOrderWithCapture($order, $capture, $fromReturn);
    }

    public function fulfillOrderWithCapture(Order $order, array $capture, bool $fromReturn): void
    {
        $captureId = (string) ($capture['id'] ?? '');
        $capturedAmount = $this->parseCaptureAmountCents($capture);
        $capturedCurrency = strtoupper((string) ($capture['amount']['currency_code'] ?? $order->currency ?? 'USD'));

        if ($capturedAmount !== (int) $order->amount_cents || $capturedCurrency !== strtoupper((string) $order->currency)) {
            $order->forceFill([
                'status' => 'failed',
                'paypal_capture_id' => $captureId === '' ? $order->paypal_capture_id : $captureId,
            ])->save();

            $this->auditService->logEvent('payment.amount_mismatch', $order, [
                'expected_amount_cents' => (int) $order->amount_cents,
                'received_amount_cents' => $capturedAmount,
                'expected_currency' => strtoupper((string) $order->currency),
                'received_currency' => $capturedCurrency,
                'capture_id' => $captureId,
                'from_return' => $fromReturn,
            ]);

            throw new RuntimeException('PAYPAL_CAPTURE_AMOUNT_MISMATCH');
        }

        DB::transaction(function () use ($order, $capture, $fromReturn) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $captureId = (string) ($capture['id'] ?? '');

            if (! empty($order->paypal_capture_id)) {
                if ($order->paypal_capture_id === $captureId) {
                    $this->auditService->logEvent('paypal_fulfill_idempotent_skip', $order, [
                        'capture_id' => $captureId,
                        'from_return' => $fromReturn,
                    ]);

                    return;
                }

                $this->auditService->logEvent('paypal_fulfill_capture_conflict', $order, [
                    'incoming_capture_id' => $captureId,
                    'stored_capture_id' => (string) $order->paypal_capture_id,
                    'from_return' => $fromReturn,
                ]);

                return;
            }

            if ($captureId === '') {
                $this->auditService->logEvent('paypal_fulfill_invalid_capture', $order, [
                    'reason' => 'missing_capture_id',
                    'from_return' => $fromReturn,
                ]);

                return;
            }

            $order->forceFill([
                'paypal_capture_id' => $captureId,
                'status' => 'paid',
                'paid_at' => now(),
            ])->save();

            $quote = PriceQuote::query()->whereKey($order->quote_id)->first();
            if ($quote !== null) {
                $quote->status = 'consumed';
                $quote->save();
            }

            $productIds = $this->resolveProductIdsForOrder($order->product_id);

            foreach ($productIds as $productId) {
                Entitlement::firstOrCreate([
                    'user_id' => $order->user_id,
                    'product_id' => $productId,
                    'order_id' => $order->id,
                    'active' => true,
                ], [
                    'active' => true,
                ]);

                $existingLicense = License::query()
                    ->where('user_id', $order->user_id)
                    ->where('product_id', $productId)
                    ->first();

                if ($existingLicense === null) {
                    License::create([
                        'user_id' => $order->user_id,
                        'product_id' => $productId,
                        'license_key' => $this->buildLicenseKey($order->user_id, $productId, $order->id),
                        'status' => 'active',
                    ]);
                }
            }

            $this->auditService->logEvent('paypal_fulfillment_success', $order, [
                'from_return' => $fromReturn,
                'capture_id' => $captureId,
                'product_count' => count($productIds),
            ]);
        });
    }

    private function resolveProductIdsForOrder(int $productId): array
    {
        $product = Product::find($productId);
        if ($product === null) {
            return [$productId];
        }

        if (! $product->is_bundle) {
            return [$productId];
        }

        $bundleItems = BundleItem::query()
            ->where('bundle_product_id', $productId)
            ->pluck('item_product_id')
            ->all();

        if (! empty($bundleItems)) {
            return $bundleItems;
        }

        return [$productId];
    }

    private function parseCaptureAmountCents(array $capture): int
    {
        $raw = $capture['amount']['value'] ?? 0;
        $normalized = preg_replace('/[^\d.]+/', '', (string) $raw);

        return (int) round((float) $normalized * 100);
    }

    private function buildLicenseKey(int $userId, int $productId, int $orderId): string
    {
        return substr(hash('sha256', $userId . ':' . $productId . ':' . $orderId . ':' . config('app.key', 'fallback')), 0, 32);
    }
}
