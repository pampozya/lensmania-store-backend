<?php

use App\Models\License;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
});

it('activates an active lowercase legacy license when entered uppercase', function () {
    $user = User::factory()->create();
    $product = Product::query()->firstOrCreate(
        ['slug' => 'cinecut'],
        [
            'name' => 'CineCut',
            'price_cents' => 5000,
            'active' => true,
        ],
    );

    License::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'license_key' => '20d3252e9013a56ae3176f7b9863158b',
        'kind' => 'paid',
        'status' => 'active',
    ]);

    $this->postJson('/api/license/activate', [
        'license_key' => '20D3252E9013A56AE3176F7B9863158B',
        'device_id' => 'resolve-device-001',
        'platform' => 'mac-arm64',
        'app_version' => '0.2.1',
    ])
        ->assertOk()
        ->assertJsonPath('allowed', true)
        ->assertJsonPath('license_key', '20d3252e9013a56ae3176f7b9863158b')
        ->assertJsonPath('license_kind', 'paid');
});
