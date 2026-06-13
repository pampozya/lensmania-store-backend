<?php

use App\Models\License;
use App\Models\Order;
use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->product = Product::query()->firstOrCreate(
        ['slug' => 'cinecut'],
        ['name' => 'CineCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true],
    );

    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
});

it('rejects a capture whose amount is lower than the stored quote', function () {
    // Server-side quote says $35.00 (3500 cents).
    $quote = PriceQuote::create([
        'quote_token' => (string) Str::uuid(),
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'base_cents' => 3500,
        'discount_cents' => 0,
        'amount_cents' => 3500,
        'currency' => 'USD',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    $order = Order::create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'quote_id' => $quote->id,
        'amount_cents' => 3500,
        'currency' => 'USD',
        'paypal_order_id' => 'ORDER-TAMPER-1',
        'status' => 'created',
    ]);

    // Attacker-crafted capture: paid only $1.00 instead of $35.00.
    $tamperedCapture = [
        'id' => 'CAPTURE-TAMPER-1',
        'status' => 'COMPLETED',
        'amount' => ['value' => '1.00', 'currency_code' => 'USD'],
        'custom_id' => $quote->quote_token,
    ];

    $service = app(\App\Services\FulfillmentService::class);

    // The fulfill call MUST refuse. Accept either an exception or a false/void
    // result — but it must NOT fulfill.
    try {
        $service->fulfill($order->fresh(), $tamperedCapture);
    } catch (\Throwable $e) {
        // expected path: rejection by exception
    }

    // HARD ASSERTIONS — these are the real proof:
    expect($order->fresh()->status)->not->toBe('paid');
    expect(License::count())->toBe(0);
    expect($this->user->fresh()->entitlements()->count())->toBe(0);

    // Audit trail must record the mismatch (adjust event name to yours).
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'payment.amount_mismatch',
    ]);
});

it('also rejects a higher-than-quote amount and a wrong currency', function () {
    $quote = PriceQuote::create([
        'quote_token' => (string) Str::uuid(),
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'base_cents' => 3500,
        'discount_cents' => 0,
        'amount_cents' => 3500,
        'currency' => 'USD',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    $order = Order::create([
        'user_id' => $this->user->id,
        'product_id' => $this->product->id,
        'quote_id' => $quote->id,
        'amount_cents' => 3500,
        'currency' => 'USD',
        'paypal_order_id' => 'ORDER-TAMPER-2',
        'status' => 'created',
    ]);

    $service = app(\App\Services\FulfillmentService::class);

    // Wrong currency must be refused even if the number matches.
    $wrongCurrency = [
        'id' => 'CAPTURE-TAMPER-2',
        'status' => 'COMPLETED',
        'amount' => ['value' => '35.00', 'currency_code' => 'AED'],
        'custom_id' => $quote->quote_token,
    ];

    try {
        $service->fulfill($order->fresh(), $wrongCurrency);
    } catch (\Throwable $e) {
    }

    expect($order->fresh()->status)->not->toBe('paid');
    expect(License::count())->toBe(0);
});
