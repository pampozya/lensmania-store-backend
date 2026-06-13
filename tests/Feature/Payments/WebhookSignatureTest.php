<?php

use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->product = Product::factory()->create([
        'slug' => 'cinecut-signature',
        'name' => 'CineCut',
        'price_cents' => 3500,
        'is_bundle' => false,
    ]);

    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
});

it('rejects forged paypal webhooks', function () {
    config(['paypal.webhook_id' => 'test-webhook-id']);

    $payload = [
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => [
            'id' => 'CAPTURE-FORGED-1',
            'status' => 'COMPLETED',
            'amount' => [
                'value' => '35.00',
                'currency_code' => 'USD',
            ],
            'custom_id' => 'ORDER-NOT-FOUND-1',
            'invoice_id' => 'ORDER-NOT-FOUND-1',
        ],
    ];

    $response = $this->postJson('/api/webhooks/paypal', $payload);

    $response->assertStatus(400);
    $response->assertJson(['ok' => false]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'paypal_webhook_signature_invalid',
    ]);
});
