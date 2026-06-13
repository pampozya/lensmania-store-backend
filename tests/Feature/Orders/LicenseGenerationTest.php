<?php

use App\Models\Order;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Support\Facades\Mail;

it('generates a license key on fulfill', function () {
    Mail::fake();

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'product_slug' => 'cinecut',
        'api_status' => 'paid',
        'license_key' => null,
    ]);

    $order->update(['api_status' => 'fulfilled']);
    $order->refresh();

    expect($order->license_key)->toMatch('/^LM-CINECUT-' . now()->year . '-[0-9ABCDEFGHJKMNPQRSTVWXYZ]{8}-[0-9ABCDEFGHJKMNPQRSTVWXYZ]{2}$/');
});

it('generates the expected license key format', function () {
    $license = LicenseService::generate('cinecut', 2026);

    expect($license)->toMatch('/^LM-CINECUT-2026-[0-9ABCDEFGHJKMNPQRSTVWXYZ]{8}-[0-9ABCDEFGHJKMNPQRSTVWXYZ]{2}$/');
});

it('generates unique license keys', function () {
    expect(LicenseService::generate('cinecut'))->not->toBe(LicenseService::generate('cinecut'));
});
