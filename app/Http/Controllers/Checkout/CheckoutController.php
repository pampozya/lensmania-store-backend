<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutQuoteRequest;
use App\Http\Requests\CreatePaypalOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
        private FulfillmentService $fulfillmentService,
    ) {}

    public function staticIntent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_slug' => ['required', 'string', 'in:hushcut,babelcut,bundle'],
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
                'name' => match ($data['product_slug']) {
                    'hushcut' => 'HushCut',
                    'babelcut' => 'BabelCut',
                    default => 'Studio Pass',
                },
                'price_cents' => match ($data['product_slug']) {
                    'bundle' => 5000,
                    default => 3500,
                },
                'is_bundle' => $data['product_slug'] === 'bundle',
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

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'status' => $order->derived_status,
            'selection_metadata' => $order->selection_metadata,
        ], 201);
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
        $order = $this->checkoutService->captureReturnedPayPalOrder(
            (string) $request->query('token', '')
        );

        if ($order instanceof \App\Models\Order) {
            $this->fulfillmentService->fulfillIfNeededFromWebhookOrReturn($order, true);
        }

        return redirect()->route('checkout.thank-you');
    }

    public function cancel(): RedirectResponse
    {
        return redirect('/')->with('checkout', 'cancelled');
    }

    public function paypalWebhook(Request $request): JsonResponse
    {
        if (! $this->checkoutService->handlePayPalWebhook($request)) {
            return response()->json(['ok' => false], 400);
        }

        return response()->json(['ok' => true]);
    }
}
