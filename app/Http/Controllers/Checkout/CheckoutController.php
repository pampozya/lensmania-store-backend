<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutQuoteRequest;
use App\Http\Requests\CreatePaypalOrderRequest;
use App\Jobs\FulfillOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\StorefrontPromo;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
        private FulfillmentService $fulfillmentService,
    ) {}

    public function staticIntent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_slug' => ['required', 'string', 'in:cinecut'],
            'promo_code' => ['nullable', 'string', 'max:64'],
            'amount_usd' => ['required', 'numeric', 'min:0'],
            'checkout_url' => ['required', 'string', 'max:2048'],
            'selection_metadata' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $product = Product::firstOrCreate(
            ['slug' => $data['product_slug']],
            [
                'name' => 'CineCut',
                'price_cents' => 3500,
                'is_bundle' => false,
                'active' => true,
            ]
        );

        $amountCents = (int) round(((float) $data['amount_usd']) * 100);
        $metadata = $data['selection_metadata'] ?? [];
        $metadata['checkout_url'] = $data['checkout_url'];

        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'amount_cents' => $amountCents,
            'amount_usd' => number_format($amountCents / 100, 2, '.', ''),
            'currency' => 'USD',
            'status' => 'created',
            'api_status' => 'pending',
            'promo_code' => isset($data['promo_code']) ? strtoupper((string) $data['promo_code']) : null,
            'selection_metadata' => $metadata,
            'purchased_at' => now(),
        ]);

        // Auto-fulfill immediately for $0 orders (100% promo codes).
        // Dispatched as a job so any exception cannot escape into this HTTP response.
        // With QUEUE_CONNECTION=sync it runs immediately; with a worker it's async.
        $fulfillError = null;
        if ($amountCents === 0) {
            try {
                FulfillOrder::dispatch($order);
            } catch (\Throwable $e) {
                $fulfillError = $e->getMessage();
                report($e);
            }
        }

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'status' => $order->derived_status,
            'selection_metadata' => $order->selection_metadata,
            'fulfill_error' => $fulfillError, // null in production normally; visible for debugging
        ], 201);
    }

    public function createStorefrontPayPalOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_slug' => ['required', 'string', 'in:cinecut'],
            'promo_code' => ['nullable', 'string', 'max:64'],
            'amount_usd' => ['required', 'numeric', 'min:0'],
            'selection_metadata' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $product = $this->storefrontProduct($data['product_slug']);
        $promoCode = $this->normalizePromoCode($data['promo_code'] ?? null);
        $amountCents = $this->storefrontAmountCents($product, $promoCode);
        $clientAmountCents = (int) round(((float) $data['amount_usd']) * 100);

        if ($clientAmountCents !== $amountCents) {
            throw ValidationException::withMessages([
                'amount_usd' => 'Checkout amount does not match the current server price.',
            ]);
        }

        $metadata = $data['selection_metadata'] ?? [];
        $metadata['checkout_source'] = 'paypal_orders_api';
        $metadata['server_amount_usd'] = number_format($amountCents / 100, 2, '.', '');

        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'amount_cents' => $amountCents,
            'amount_usd' => number_format($amountCents / 100, 2, '.', ''),
            'currency' => 'USD',
            'status' => 'created',
            'api_status' => 'pending',
            'promo_code' => $promoCode,
            'selection_metadata' => $metadata,
            'purchased_at' => now(),
        ]);

        if ($amountCents === 0) {
            $fulfilled = $this->fulfillmentService->fulfillStaticOrder($order);

            return response()->json([
                'ok' => true,
                'order_id' => $order->id,
                'status' => $order->fresh()->derived_status,
                'fulfilled' => $fulfilled,
                'approve_url' => $this->frontendUrl('/thank-you?payment=confirmed&order=' . $order->id),
            ], 201);
        }

        try {
            $approveUrl = $this->checkoutService->createPayPalOrderForOrder(
                $order,
                null,
                url('/checkout/return'),
                $this->frontendUrl('/buy?payment=cancelled#' . $product->slug),
            );
        } catch (\Throwable $e) {
            $order->forceFill(['status' => 'failed'])->save();
            Log::error('paypal_order_create_failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'PayPal checkout is temporarily unavailable'], 502);
        }

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'paypal_order_id' => $order->fresh()->paypal_order_id,
            'approve_url' => $approveUrl,
        ], 201);
    }

    /**
     * Called by the React thank-you page after returning from PayPal.
     * Fulfills the most recent pending static order for the logged-in user.
     */
    /**
     * Called by the React thank-you page after returning from PayPal.
     * Only auto-fulfills $0 orders (100% promo — no payment needed).
     * Paid orders require PayPal IPN confirmation and are fulfilled manually from Filament.
     */
    public function fulfillPending(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $order = Order::where('user_id', $user->id)
            ->where('api_status', 'pending')
            ->where('amount_cents', 0)
            ->latest('created_at')
            ->first();

        if (! $order) {
            // Paid orders: tell the frontend to wait — admin will fulfill after PayPal confirms
            return response()->json(['ok' => true, 'fulfilled' => false, 'message' => 'Pending payment confirmation']);
        }

        try {
            $fulfilled = $this->fulfillmentService->fulfillStaticOrder($order);
            return response()->json(['ok' => true, 'fulfilled' => $fulfilled, 'order_id' => $order->id]);
        } catch (\Throwable $e) {
            Log::error('fulfill_pending_failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'Fulfillment failed'], 500);
        }
    }

    public function quote(CheckoutQuoteRequest $request): JsonResponse
    {
        $result = $this->checkoutService->createQuote(
            $request->user(),
            $request->validated('product_slug'),
            $request->validated('promo_code'),
            $request->validated('affiliate_code')
        );

        return response()->json($result);
    }

    public function createPayPalOrder(CreatePaypalOrderRequest $request): RedirectResponse
    {
        $url = $this->checkoutService->createPayPalOrder(
            $request->user(),
            $request->validated('quote_token')
        );

        return redirect()->away($url);
    }

    public function return(Request $request): RedirectResponse
    {
        try {
            $order = $this->checkoutService->captureReturnedPayPalOrder(
                (string) $request->query('token', '')
            );

            if ($order instanceof Order) {
                return redirect()->away($this->frontendUrl('/thank-you?payment=confirmed&order=' . $order->id));
            }
        } catch (\Throwable $e) {
            Log::error('paypal_return_capture_failed', [
                'paypal_order_id' => (string) $request->query('token', ''),
                'error' => $e->getMessage(),
            ]);

            return redirect()->away($this->frontendUrl('/thank-you?payment=error'));
        }

        return redirect()->away($this->frontendUrl('/thank-you?payment=pending'));
    }

    public function cancel(): RedirectResponse
    {
        return redirect()->away($this->frontendUrl('/buy?payment=cancelled'));
    }

    public function paypalWebhook(Request $request): JsonResponse
    {
        if (! $this->checkoutService->handlePayPalWebhook($request)) {
            return response()->json(['ok' => false], 400);
        }

        return response()->json(['ok' => true]);
    }

    private function storefrontProduct(string $slug): Product
    {
        return Product::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => 'CineCut',
                'price_cents' => 3500,
                'is_bundle' => false,
                'active' => true,
            ]
        );
    }

    private function storefrontAmountCents(Product $product, ?string $promoCode): int
    {
        $baseCents = (int) $product->price_cents;

        if ($promoCode === null) {
            return $baseCents;
        }

        if ($promoCode === 'TEST100') {
            throw ValidationException::withMessages(['promo_code' => 'Promo code is unavailable.']);
        }

        $promo = StorefrontPromo::query()
            ->where('code', $promoCode)
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (! $promo) {
            throw ValidationException::withMessages(['promo_code' => 'Promo code is not valid.']);
        }

        $fixedPrice = match ($product->slug) {
            'cinecut' => $promo->price_cinecut,
            default => null,
        };

        if ($fixedPrice !== null) {
            return max(0, (int) round(((float) $fixedPrice) * 100));
        }

        if ($promo->discount_percent !== null) {
            return max(0, (int) round($baseCents * (1 - ((float) $promo->discount_percent / 100))));
        }

        return $baseCents;
    }

    private function normalizePromoCode(?string $code): ?string
    {
        $normalized = strtoupper((string) preg_replace('/\s+/', '', (string) $code));

        return $normalized === '' ? null : $normalized;
    }

    private function frontendUrl(string $path): string
    {
        return rtrim((string) config('app.frontend_url'), '/') . '/' . ltrim($path, '/');
    }
}
