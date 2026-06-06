<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutQuoteRequest;
use App\Http\Requests\CreatePaypalOrderRequest;
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
