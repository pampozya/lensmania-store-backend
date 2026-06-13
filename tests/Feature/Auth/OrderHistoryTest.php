<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\JwtService;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'owner@example.com',
        'password' => 'super-secret',
    ]);

    $this->otherUser = User::factory()->create([
        'email' => 'other@example.com',
        'password' => 'super-secret',
    ]);

    $this->cinecut = Product::query()->firstOrCreate(
        ['slug' => 'cinecut'],
        ['name' => 'CineCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true],
    );

    $this->userToken = app(JwtService::class)->issue($this->user);

    Order::factory()->create([
        'user_id' => $this->user->id,
        'product_id' => $this->cinecut->id,
        'product_slug' => 'cinecut',
        'amount_usd' => 35.00,
        'promo_code' => 'NOOR10',
        'status' => 'paid',
        'api_status' => 'fulfilled',
        'license_key' => 'LM-CINECUT-2026-ABC123',
        'download_url' => 'https://example.com/cinecut.dmg',
        'purchased_at' => now(),
    ]);

    Order::factory()->create([
        'user_id' => $this->otherUser->id,
        'product_id' => $this->cinecut->id,
        'product_slug' => 'cinecut',
        'amount_usd' => 35.00,
        'promo_code' => null,
        'status' => 'paid',
        'api_status' => 'pending',
        'purchased_at' => now(),
    ]);
});

it('returns only current user orders', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
        ->getJson('/api/auth/orders');

    $response->assertStatus(200);

    $rows = $response->json();
    expect($rows)->toBeArray();
    expect(count($rows))->toBe(1);
    expect($rows[0]['product_name'])->toBe('CineCut');
    expect($rows[0]['product_slug'])->toBe('cinecut');
    expect($rows[0]['license_key'])->toBe('LM-CINECUT-2026-ABC123');
});

it('returns 401 on orders without token', function () {
    $this->getJson('/api/auth/orders')->assertStatus(401)->assertJson(['error' => 'Unauthorized']);
});

it('includes amount, status, and download link when fulfilled', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken)
        ->getJson('/api/auth/orders');

    $response->assertStatus(200);
    $response->assertJsonFragment([
        'amount_usd' => 35.0,
        'status' => 'fulfilled',
        'product_name' => 'CineCut',
        'license_key' => 'LM-CINECUT-2026-ABC123',
        'download_url' => 'https://example.com/cinecut.dmg',
    ]);
});
