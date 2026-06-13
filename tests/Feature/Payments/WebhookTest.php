<?php

use App\Models\Product;
use App\Models\User;

it('creates a paid order from a valid webhook payload', function () {
    config(['paypal.webhook_id' => 'test-webhook-id']);

    $user = User::factory()->create();
    Product::query()->firstOrCreate(
        ['slug' => 'cinecut'],
        ['name' => 'CineCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true],
    );

    $response = $this->withHeaders([
        'PAYPAL-TRANSMISSION-ID' => 'tx-valid-1',
        'PAYPAL-TRANSMISSION-SIG' => 'sig-valid-1',
        'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
    ])->postJson('/api/payments/webhook', [
        'txn_type' => 'web_accept',
        'mc_gross' => '35.00',
        'mc_currency' => 'USD',
        'txn_id' => 'TXN-MANUAL-1',
        'custom' => $user->id . ':cinecut',
        'invoice' => 'NOOR10',
        'payment_status' => 'Completed',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['received' => true]);

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'product_slug' => 'cinecut',
        'paypal_payment_id' => 'TXN-MANUAL-1',
        'api_status' => 'paid',
    ]);
});

it('does not duplicate order for duplicate txn_id', function () {
    config(['paypal.webhook_id' => 'test-webhook-id']);

    $user = User::factory()->create();
    Product::query()->firstOrCreate(
        ['slug' => 'cinecut'],
        ['name' => 'CineCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true],
    );

    $payload = [
        'txn_type' => 'web_accept',
        'mc_gross' => '35.00',
        'mc_currency' => 'USD',
        'txn_id' => 'TXN-MANUAL-DUP',
        'custom' => $user->id . ':cinecut',
        'payment_status' => 'Completed',
    ];

    $this->withHeaders([
        'PAYPAL-TRANSMISSION-ID' => 'tx-dup-1',
        'PAYPAL-TRANSMISSION-SIG' => 'sig-dup-1',
        'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
    ])->postJson('/api/payments/webhook', $payload)->assertOk();

    $this->withHeaders([
        'PAYPAL-TRANSMISSION-ID' => 'tx-dup-2',
        'PAYPAL-TRANSMISSION-SIG' => 'sig-dup-2',
        'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
    ])->postJson('/api/payments/webhook', $payload)->assertOk();

    $this->assertDatabaseCount('orders', 1);
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'product_slug' => 'cinecut',
        'paypal_payment_id' => 'TXN-MANUAL-DUP',
    ]);
});

it('returns received when webhook signature fails and logs an error', function () {
    config(['paypal.webhook_id' => '']);

    $user = User::factory()->create();
    Product::query()->firstOrCreate(
        ['slug' => 'cinecut'],
        ['name' => 'CineCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true],
    );

    $response = $this->postJson('/api/payments/webhook', [
        'txn_type' => 'web_accept',
        'mc_gross' => '35.00',
        'mc_currency' => 'USD',
        'txn_id' => 'TXN-MANUAL-SIGN',
        'custom' => $user->id . ':cinecut',
        'payment_status' => 'Completed',
    ]);

    $response->assertStatus(200)
        ->assertJson(['received' => true]);

    $this->assertDatabaseHas('audit_logs', ['event' => 'payment_webhook_invalid_signature']);
});
