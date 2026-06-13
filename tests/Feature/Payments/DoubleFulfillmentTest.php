<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\License;
use App\Models\Entitlement;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->product = Product::factory()->create([
        'slug' => 'cinecut-double',
        'name' => 'CineCut',
        'price_cents' => 3500,
        'is_bundle' => false,
    ]);

    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    config(['paypal.webhook_id' => 'test-webhook-id']);
});

it('prevents double fulfillment when both return and webhook fire', function () {
    /** @var CheckoutService $checkoutService */
    $checkoutService = app(CheckoutService::class);

    $quote = $checkoutService->createQuote($this->user, $this->product->slug, null, null);
    $checkoutService->createPayPalOrder($this->user, $quote['quote_token']);

    $order = Order::query()
        ->where('user_id', $this->user->id)
        ->where('product_id', $this->product->id)
        ->latest()
        ->firstOrFail();

    $checkoutService->captureReturnedPayPalOrder($order->paypal_order_id);

    $order = $order->fresh();
    expect($order->status)->toBe('paid');

    $captureId = (string) $order->paypal_capture_id;
    expect($captureId)->not->toBe('');

    $webhookResponse = $this->withHeaders([
        'PAYPAL-TRANSMISSION-ID' => 'tx-double-1',
        'PAYPAL-TRANSMISSION-SIG' => 'sig-double-1',
        'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
    ])->postJson('/api/webhooks/paypal', [
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => [
            'id' => $captureId,
            'status' => 'COMPLETED',
            'amount' => [
                'value' => '35.00',
                'currency_code' => 'USD',
            ],
            'custom_id' => $order->paypal_order_id,
            'invoice_id' => $order->paypal_order_id,
        ],
    ]);

    $webhookResponse->assertOk();
    $webhookResponse->assertJson(['ok' => true]);

    $order = $order->fresh();
    expect($order->paypal_capture_id)->toBe($captureId);

    $this->assertDatabaseCount('licenses', 1);
    $this->assertDatabaseCount('entitlements', 1);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'paypal_capture_id' => $captureId,
        'status' => 'paid',
    ]);
    $this->assertSame(
        1,
        Order::query()->where('paypal_capture_id', $captureId)->count(),
    );
    $this->assertSame(
        1,
        License::query()->where('user_id', $this->user->id)->where('product_id', $this->product->id)->count(),
    );
    $this->assertSame(
        1,
        Entitlement::query()->where('user_id', $this->user->id)->where('product_id', $this->product->id)->count(),
    );
});
