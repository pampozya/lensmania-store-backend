<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PriceQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayPalService
{
    public function createOrder(array $payload): array
    {
        if ($this->usingFakeGateway()) {
            return $this->fakeOrder($payload);
        }

        $amountCents = (int) ($payload['amount_cents'] ?? 0);
        $currency = strtoupper((string) ($payload['currency'] ?? 'USD'));
        $localOrderId = (string) ($payload['order_id'] ?? '');
        $purchaseUnit = [
            'reference_id' => $localOrderId !== '' ? 'LM-' . $localOrderId : (string) ($payload['quote_token'] ?? Str::uuid()),
            'description' => substr((string) ($payload['description'] ?? 'Lensmania Labs license'), 0, 127),
            'amount' => [
                'currency_code' => $currency,
                'value' => number_format($amountCents / 100, 2, '.', ''),
            ],
        ];

        if ($localOrderId !== '') {
            $purchaseUnit['custom_id'] = $localOrderId;
            $purchaseUnit['invoice_id'] = 'LM-' . $localOrderId;
        }

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'PayPal-Request-Id' => 'lensmania-order-' . ($localOrderId !== '' ? $localOrderId : Str::uuid()),
            ])
            ->timeout((int) config('paypal.timeout', 30))
            ->post($this->baseUri() . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [$purchaseUnit],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                            'brand_name' => 'Lensmania Labs',
                            'landing_page' => 'LOGIN',
                            'shipping_preference' => 'NO_SHIPPING',
                            'user_action' => 'PAY_NOW',
                            'return_url' => (string) ($payload['return_url'] ?? url('/checkout/return')),
                            'cancel_url' => (string) ($payload['cancel_url'] ?? config('app.frontend_url') . '/buy?payment=cancelled'),
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PAYPAL_ORDER_CREATE_FAILED:' . $response->status());
        }

        $data = $response->json();
        $approvalLink = collect($data['links'] ?? [])
            ->first(fn (array $link): bool => in_array(($link['rel'] ?? ''), ['approve', 'payer-action'], true));
        $approvalUrl = is_array($approvalLink) ? ($approvalLink['href'] ?? null) : null;

        if (! is_string($approvalUrl) || $approvalUrl === '') {
            throw new RuntimeException('PAYPAL_APPROVAL_URL_MISSING');
        }

        return [
            'id' => (string) ($data['id'] ?? ''),
            'approve_url' => $approvalUrl,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'quote_token' => (string) ($payload['quote_token'] ?? ''),
        ];
    }

    public function captureOrder(string $paypalOrderId): array
    {
        if ($this->usingFakeGateway()) {
            return $this->fakeCapture($paypalOrderId);
        }

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'PayPal-Request-Id' => 'lensmania-capture-' . $paypalOrderId,
            ])
            ->timeout((int) config('paypal.timeout', 30))
            ->post($this->baseUri() . '/v2/checkout/orders/' . urlencode($paypalOrderId) . '/capture', new \stdClass());

        if (! $response->successful()) {
            throw new RuntimeException('PAYPAL_ORDER_CAPTURE_FAILED:' . $response->status());
        }

        $data = $response->json();
        $purchaseUnit = $data['purchase_units'][0] ?? [];
        $capture = $purchaseUnit['payments']['captures'][0] ?? [];

        return [
            'id' => (string) ($capture['id'] ?? ''),
            'status' => (string) ($capture['status'] ?? $data['status'] ?? ''),
            'capture_status' => (string) ($capture['status'] ?? ''),
            'amount' => is_array($capture['amount'] ?? null) ? $capture['amount'] : [
                'value' => (string) ($purchaseUnit['amount']['value'] ?? '0.00'),
                'currency_code' => (string) ($purchaseUnit['amount']['currency_code'] ?? 'USD'),
            ],
            'custom_id' => (string) ($capture['custom_id'] ?? $purchaseUnit['custom_id'] ?? ''),
            'invoice_id' => (string) ($capture['invoice_id'] ?? $purchaseUnit['invoice_id'] ?? ''),
        ];
    }

    public function verifyWebhookSignature(Request $request, string $webhookId): bool
    {
        if ($this->usingFakeGateway()) {
            return true;
        }

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('paypal.timeout', 30))
            ->post($this->baseUri() . '/v1/notifications/verify-webhook-signature', [
                'auth_algo' => (string) $request->header('PAYPAL-AUTH-ALGO', ''),
                'cert_url' => (string) $request->header('PAYPAL-CERT-URL', ''),
                'transmission_id' => (string) $request->header('PAYPAL-TRANSMISSION-ID', ''),
                'transmission_sig' => (string) $request->header('PAYPAL-TRANSMISSION-SIG', ''),
                'transmission_time' => (string) $request->header('PAYPAL-TRANSMISSION-TIME', ''),
                'webhook_id' => $webhookId,
                'webhook_event' => $request->all(),
            ]);

        return $response->ok() && $response->json('verification_status') === 'SUCCESS';
    }

    public function usingFakeGateway(): bool
    {
        return app()->environment('testing');
    }

    public function getQuoteForOrder(string $paypalOrderId): ?PriceQuote
    {
        $order = Order::where('paypal_order_id', $paypalOrderId)->first();

        if (!$order) {
            return null;
        }

        return PriceQuote::find($order->quote_id);
    }

    private function fakeOrder(array $payload): array
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

    private function fakeCapture(string $paypalOrderId): array
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

    private function configured(): bool
    {
        return (string) config('paypal.client_id', '') !== ''
            && (string) config('paypal.secret', '') !== '';
    }

    private function accessToken(): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('PAYPAL_NOT_CONFIGURED');
        }

        $response = Http::asForm()
            ->withBasicAuth((string) config('paypal.client_id'), (string) config('paypal.secret'))
            ->acceptJson()
            ->timeout((int) config('paypal.timeout', 30))
            ->post($this->baseUri() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PAYPAL_ACCESS_TOKEN_FAILED:' . $response->status());
        }

        $token = (string) ($response->json('access_token') ?? '');
        if ($token === '') {
            throw new RuntimeException('PAYPAL_ACCESS_TOKEN_MISSING');
        }

        return $token;
    }

    private function baseUri(): string
    {
        if ((string) config('paypal.env') === 'live') {
            return rtrim((string) config('paypal.live_base_uri'), '/');
        }

        return rtrim((string) config('paypal.base_uri'), '/');
    }
}
