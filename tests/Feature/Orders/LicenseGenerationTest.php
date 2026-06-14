<?php

use App\Models\Order;
use App\Models\Entitlement;
use App\Models\License;
use App\Models\Trial;
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

it('issues a trial license from the backend command', function () {
    $email = 'trial-command@example.com';

    $this->artisan('license:issue', [
        'email' => $email,
        'kind' => 'trial',
        '--create-user' => true,
        '--json' => true,
    ])->assertSuccessful();

    $user = User::query()->where('email', $email)->firstOrFail();
    $trial = Trial::query()->where('user_id', $user->id)->firstOrFail();
    $license = License::query()->where('user_id', $user->id)->firstOrFail();

    expect($trial->status)->toBe('active');
    expect($license->kind)->toBe('trial');
    expect($license->status)->toBe('active');
    expect($license->license_key)->toStartWith('LM-CINECUT-TRIAL-');
    expect($license->expires_at?->toIso8601String())->toBe($trial->expires_at?->toIso8601String());
    expect(Entitlement::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('issues a full paid license from the backend command', function () {
    $email = 'full-command@example.com';

    $this->artisan('license:issue', [
        'email' => $email,
        'kind' => 'full',
        '--create-user' => true,
        '--json' => true,
    ])->assertSuccessful();

    $user = User::query()->where('email', $email)->firstOrFail();
    $license = License::query()->where('user_id', $user->id)->firstOrFail();
    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    expect($license->kind)->toBe('paid');
    expect($license->status)->toBe('active');
    expect($license->license_key)->toStartWith('LM-CINECUT-');
    expect($license->license_key)->not->toStartWith('LM-CINECUT-TRIAL-');
    expect($license->expires_at)->toBeNull();
    expect($order->status)->toBe('paid');
    expect($order->api_status)->toBe('manual_grant');
    expect(Entitlement::query()->where('user_id', $user->id)->where('order_id', $order->id)->exists())->toBeTrue();
});
