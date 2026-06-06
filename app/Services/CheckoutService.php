<?php

namespace App\Services;

use App\Contracts\PayPalWebhookVerifier;
use App\Models\Order;
use App\Models\PriceQuote;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private PayPalService $payPalService,
        private FulfillmentService $fulfillmentService,
        private AuditService $auditService,
        private PayPalWebhookVerifier $payPalWebhookVerifier,
    ) {}

    public function createQuote($user, string $productSlug, ?string $promoCode, ?string $affiliateCode)
    {
        $product = Product::query()->where('slug', $productSlug)->firstOrFail();

        $quote = PriceQuote::create([
            'quote_token' => (string) Str::uuid(),
            'user_id' => $user->id,
            'product_id' => $product->id,
            'base_cents' => (int) $product->price_cents,
            'discount_cents' => 0,
            'amount_cents' => (int) $product->price_cents,
            'currency' => 'USD',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
            'promo_code_id' => null,
            'affiliate_id' => null,
        ]);

        $this->auditService->logEvent('quote_created', null, [
            'quote_id' => $quote->id,
            'product_slug' => $productSlug,
            'promo_code' => $promoCode,
            'affiliate_code' => $affiliateCode,
        ]);

        return [
            'quote_token' => $quote->quote_token,
            'amount_cents' => (int) $quote->amount_cents,
            'currency' => 'USD',
            'expires_at' => now()->addMinutes(30)->toIso8601String(),
            'product_slug' => $productSlug,
            'promo_code' => $promoCode,
            'affiliate_code' => $affiliateCode,
        ];
    }

    public function createPayPalOrder($user, string $quoteToken): string
    {
        $quote = PriceQuote::query()->where('quote_token', $quoteToken)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $quote->product_id,
            'quote_id' => $quote->id,
            'amount_cents' => $quote->amount_cents,
            'currency' => $quote->currency,
            'status' => 'created',
        ]);

        $orderData = [
            'quote_token' => $quoteToken,
            'amount_cents' => $quote->amount_cents,
            'currency' => $quote->currency,
        ];

        $order = $order->fresh();

        $payPalOrder = $this->payPalService->createOrder($orderData);

        $order->forceFill([
            'paypal_order_id' => $payPalOrder['id'],
        ])->save();

        $this->auditService->logEvent('paypal_order_created', null, [
            'quote_token' => $quoteToken,
            'order_id' => $order->id,
            'paypal_order_id' => $payPalOrder['id'],
        ]);

        return $payPalOrder['approve_url'];
    }

    public function captureReturnedPayPalOrder(string $paypalOrderId)
    {
        if ($paypalOrderId === '') {
            return null;
        }

        $order = Order::query()->where('paypal_order_id', $paypalOrderId)->first();
        if (! $order) {
            return null;
        }

        $capture = $this->payPalService->captureOrder($paypalOrderId);
        $this->fulfillmentService->fulfill($order, $capture, true);

        return $order;
    }

    public function handlePayPalWebhook(Request $request): bool
    {
        if (! $this->payPalWebhookVerifier->verify($request)) {
            $this->auditService->logEvent('paypal_webhook_signature_invalid', null, [
                'event_type' => $request->input('event_type'),
            ]);

            return false;
        }

        $payload = $request->all();
        $resource = is_array($payload['resource'] ?? null) ? $payload['resource'] : [];
        $capture = [
            'id' => (string) ($resource['id'] ?? ''),
            'status' => (string) ($resource['status'] ?? 'COMPLETED'),
            'amount' => is_array($resource['amount'] ?? null) ? $resource['amount'] : ['value' => '0.00', 'currency_code' => 'USD'],
            'custom_id' => (string) ($resource['custom_id'] ?? ''),
            'invoice_id' => (string) ($resource['invoice_id'] ?? ''),
        ];

        $paypalOrderId = $capture['custom_id'] !== '' ? $capture['custom_id'] : $capture['invoice_id'];
        if ($paypalOrderId === '') {
            $this->auditService->logEvent('paypal_webhook_missing_order_reference', null, [
                'resource_id' => $capture['id'],
            ]);

            return false;
        }

        $order = Order::query()->where('paypal_order_id', $paypalOrderId)->first();
        if (! $order) {
            $this->auditService->logEvent('paypal_webhook_order_not_found', null, [
                'paypal_order_id' => $paypalOrderId,
                'capture_id' => $capture['id'],
            ]);

            return false;
        }

        $this->fulfillmentService->fulfill($order, $capture, false);
        $this->auditService->logEvent('paypal_webhook_received', $order, ['type' => (string) $request->input('event_type')]);

        return true;
    }
}
