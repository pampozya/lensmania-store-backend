<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PriceQuote;
use Illuminate\Support\Str;
use App\Contracts\PayPalWebhookVerifier;
use Illuminate\Http\Request;
use RuntimeException;

class PayPalService
{
    public function createOrder(array $payload): array
    {
        $orderId = (string) Str::uuid();

        return [
            'id' => $orderId,
            'approve_url' => 'https://www.paypal.com/checkoutnow?token=' . $orderId,
            'amount_cents' => (int) ($payload['amount_cents'] ?? 0),
            'currency' => (string) ($payload['currency'] ?? 'USD'),
            'quote_token' => (string) ($payload['quote_token'] ?? ''),
        ];
    }

    public function captureOrder(string $paypalOrderId): array
    {
        $order = Order::where('paypal_order_id', $paypalOrderId)->firstOrFail();

        return [
            'id' => (string) Str::uuid(),
            'status' => 'COMPLETED',
            'capture_status' => 'COMPLETED',
            'amount' => [
                'value' => number_format($order->amount_cents / 100, 2, '.', ''),
                'currency_code' => $order->currency,
            ],
            'custom_id' => $paypalOrderId,
            'invoice_id' => $paypalOrderId,
        ];
    }

    public function getQuoteForOrder(string $paypalOrderId): ?PriceQuote
    {
        $order = Order::where('paypal_order_id', $paypalOrderId)->first();

        if (!$order) {
            return null;
        }

        return PriceQuote::find($order->quote_id);
    }
}
