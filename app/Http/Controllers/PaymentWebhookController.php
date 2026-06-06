<?php

namespace App\Http\Controllers;

use App\Contracts\PayPalWebhookVerifier;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    // TODO (MVP): Payment fulfillment is intentionally manual. Keep PayPal orders manual entry in Filament
    // until full webhook signature + order reconciliation API integration is reworked end-to-end.
    public function __construct(private PayPalWebhookVerifier $verifier)
    {
    }

    public function webhook(Request $request): JsonResponse
    {
        if (! $this->verifier->verify($request)) {
            $this->logWebhookFailure($request, 'payment_webhook_invalid_signature', ['reason' => 'signature_verification_failed']);
            return response()->json(['received' => true], 200);
        }

        $status = strtolower((string) ($request->input('payment_status') ?? $request->input('resource.status') ?? ''));
        $rawStatus = strtolower((string) ($request->input('txn_type') ?? $request->input('event_type') ?? ''));

        if ($status !== 'completed' && $rawStatus !== 'web_accept') {
            return response()->json(['received' => true], 200);
        }

        $custom = (string) ($request->input('custom') ?? $request->input('resource.custom_id') ?? '');
        [$userId, $productSlug] = $this->parseCustom($custom);
        if (! $userId || ! $productSlug) {
            $this->logWebhookFailure($request, 'payment_webhook_invalid_payload', ['reason' => 'missing_user_or_product']);
            return response()->json(['received' => true], 200);
        }

        $user = User::query()->find($userId);
        if (! $user) {
            $this->logWebhookFailure($request, 'payment_webhook_invalid_payload', ['reason' => 'unknown_user', 'user_id' => $userId]);
            return response()->json(['received' => true], 200);
        }

        $product = Product::query()->where('slug', $productSlug)->first();
        if (! $product) {
            $this->logWebhookFailure($request, 'payment_webhook_invalid_payload', ['reason' => 'unknown_product', 'product_slug' => $productSlug]);
            return response()->json(['received' => true], 200);
        }

        $paymentId = (string) ($request->input('txn_id') ?? $request->input('resource.id') ?? '');
        if ($paymentId === '') {
            $this->logWebhookFailure($request, 'payment_webhook_invalid_payload', ['reason' => 'missing_payment_id']);
            return response()->json(['received' => true], 200);
        }

        $amountUsd = $this->parseAmount((string) ($request->input('mc_gross') ?? $request->input('resource.amount.value') ?? ''));
        $currency = strtoupper((string) ($request->input('mc_currency') ?? $request->input('resource.amount.currency_code') ?? 'USD'));

        $order = Order::query()->firstOrNew([
            'user_id' => $user->id,
            'paypal_payment_id' => $paymentId,
        ]);

        $order->fill([
            'product_id' => $product->id,
            'product_slug' => $productSlug,
            'amount_cents' => (int) round($amountUsd * 100),
            'amount_usd' => $amountUsd,
            'promo_code' => (string) ($request->input('invoice') ?? $request->input('custom_id') ?? ''),
            'paypal_payment_id' => $paymentId,
            'status' => 'paid',
            'api_status' => 'paid',
            'purchased_at' => now(),
            'currency' => $currency,
        ]);

        $order->save();

        return response()->json(['received' => true], 200);
    }

    private function parseCustom(string $custom): array
    {
        $userId = null;
        $productSlug = null;

        if ($custom === '') {
            return [$userId, $productSlug];
        }

        if (str_contains($custom, ':')) {
            $parts = explode(':', $custom, 2);
            $userId = (int) $parts[0];
            $productSlug = trim((string) ($parts[1] ?? ''));
        } else {
            $decoded = json_decode($custom, true);
            if (is_array($decoded)) {
                $userId = isset($decoded['user_id']) ? (int) $decoded['user_id'] : null;
                $productSlug = isset($decoded['product_slug']) ? (string) $decoded['product_slug'] : null;
            }
        }

        if (! is_numeric($userId) || $userId <= 0) {
            $userId = null;
        }

        return [$userId, $productSlug ?: null];
    }

    private function parseAmount(string $value): float
    {
        if ($value === '') {
            return 0.0;
        }

        return (float) str_replace([',', ' '], '', $value);
    }

    private function logWebhookFailure(Request $request, string $event, array $meta): void
    {
        AuditLog::create([
            'actor_user_id' => null,
            'event' => $event,
            'subject_type' => 'paypal_webhook',
            'subject_id' => null,
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'meta' => array_merge($meta, ['path' => $request->path()]),
            'created_at' => now(),
        ]);
    }
}
