<?php

use App\Models\Order;
use App\Models\User;
use App\Services\JwtService;

it('creates a pending dashboard order with bundle version selections before paypal redirect', function () {
    $user = User::factory()->create();
    $token = app(JwtService::class)->issue($user);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/checkout/static-intent', [
            'product_slug' => 'bundle',
            'promo_code' => 'NOORVIP',
            'amount_usd' => 25.00,
            'checkout_url' => 'https://www.paypal.com/ncp/payment/example',
            'selection_metadata' => [
                'bundle_versions' => [
                    'hushcut' => [
                        'product' => 'hushcut',
                        'platform' => 'mac',
                        'app' => 'resolve',
                        'label' => 'macOS DaVinci Resolve',
                    ],
                    'babelcut' => [
                        'product' => 'babelcut',
                        'platform' => 'mac',
                        'app' => 'premiere',
                        'label' => 'macOS Premiere Pro',
                    ],
                ],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('selection_metadata.bundle_versions.hushcut.app', 'resolve');

    $order = Order::query()->firstOrFail();
    expect($order->user_id)->toBe($user->id);
    expect($order->product_slug)->toBe('bundle');
    expect($order->promo_code)->toBe('NOORVIP');
    expect($order->amount_cents)->toBe(2500);
    expect($order->selection_metadata['bundle_versions']['hushcut']['label'])->toBe('macOS DaVinci Resolve');
});

it('requires authentication before creating a static checkout intent', function () {
    $this->postJson('/api/checkout/static-intent', [
        'product_slug' => 'bundle',
        'amount_usd' => 25.00,
        'checkout_url' => 'https://www.paypal.com/ncp/payment/example',
    ])->assertUnauthorized();
});

it('creates a backend paypal order for storefront checkout', function () {
    $user = User::factory()->create();
    $token = app(JwtService::class)->issue($user);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/checkout/paypal-order', [
            'product_slug' => 'bundle',
            'amount_usd' => 50.00,
            'selection_metadata' => [
                'hushcut' => ['platform' => 'mac', 'app' => 'premiere'],
                'babelcut' => ['platform' => 'mac', 'app' => 'premiere'],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('ok', true)
        ->assertJsonStructure(['order_id', 'paypal_order_id', 'approve_url']);

    $order = Order::query()->latest()->firstOrFail();
    expect($order->user_id)->toBe($user->id);
    expect($order->product_slug)->toBe('bundle');
    expect($order->amount_cents)->toBe(5000);
    expect($order->paypal_order_id)->not->toBeNull();
    expect($order->api_status)->toBe('pending');
});

it('rejects disabled test promo on storefront checkout', function () {
    $user = User::factory()->create();
    $token = app(JwtService::class)->issue($user);

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/checkout/paypal-order', [
            'product_slug' => 'bundle',
            'promo_code' => 'TEST100',
            'amount_usd' => 0,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['promo_code']);
});
