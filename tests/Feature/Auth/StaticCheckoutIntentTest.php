<?php

use App\Models\Order;
use App\Models\User;
use App\Services\JwtService;

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
});

it('creates a pending dashboard order with CineCut version selection before paypal redirect', function () {
    $user = User::factory()->create();
    $token = app(JwtService::class)->issue($user);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/checkout/static-intent', [
            'product_slug' => 'cinecut',
            'promo_code' => 'NOORVIP',
            'amount_usd' => 15.00,
            'checkout_url' => 'https://www.paypal.com/ncp/payment/example',
            'selection_metadata' => [
                'product_version' => [
                    'product' => 'cinecut',
                    'platform' => 'mac-arm64',
                    'app' => 'premiere',
                    'label' => 'macOS Apple Silicon Premiere Pro',
                ],
            ]
        ]);

    $response->assertCreated()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('selection_metadata.product_version.app', 'premiere');

    $order = Order::query()->firstOrFail();
    expect($order->user_id)->toBe($user->id);
    expect($order->product_slug)->toBe('cinecut');
    expect($order->promo_code)->toBe('NOORVIP');
    expect($order->amount_cents)->toBe(1500);
    expect($order->selection_metadata['product_version']['label'])->toBe('macOS Apple Silicon Premiere Pro');
});

it('requires authentication before creating a static checkout intent', function () {
    $this->postJson('/api/checkout/static-intent', [
        'product_slug' => 'cinecut',
        'amount_usd' => 35.00,
        'checkout_url' => 'https://www.paypal.com/ncp/payment/example',
    ])->assertUnauthorized();
});

it('creates a backend paypal order for storefront checkout', function () {
    $user = User::factory()->create();
    $token = app(JwtService::class)->issue($user);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/checkout/paypal-order', [
            'product_slug' => 'cinecut',
            'amount_usd' => 35.00,
            'selection_metadata' => [
                'product_version' => ['platform' => 'mac-arm64', 'app' => 'premiere'],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('ok', true)
        ->assertJsonStructure(['order_id', 'paypal_order_id', 'approve_url']);

    $order = Order::query()->latest()->firstOrFail();
    expect($order->user_id)->toBe($user->id);
    expect($order->product_slug)->toBe('cinecut');
    expect($order->amount_cents)->toBe(3500);
    expect($order->paypal_order_id)->not->toBeNull();
    expect($order->api_status)->toBe('pending');
});

it('rejects disabled test promo on storefront checkout', function () {
    $user = User::factory()->create();
    $token = app(JwtService::class)->issue($user);

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/checkout/paypal-order', [
            'product_slug' => 'cinecut',
            'promo_code' => 'TEST100',
            'amount_usd' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['promo_code']);
});
