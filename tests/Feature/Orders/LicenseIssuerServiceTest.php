<?php

use App\Models\Entitlement;
use App\Models\License;
use App\Models\User;
use App\Services\LicenseIssuerService;

it('issues a trial license from the issuer service', function () {
    config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);

    $user = User::factory()->create();

    $result = app(LicenseIssuerService::class)->issue([
        'email' => $user->email,
        'kind' => 'trial',
        'platform' => 'mac-arm64',
        'app' => 'premiere',
    ]);

    $license = License::query()->where('user_id', $user->id)->where('kind', 'trial')->firstOrFail();

    expect($result['kind'])->toBe('trial');
    expect($result['license_key'])->toBe($license->license_key);
    expect($license->license_key)->toStartWith('LM-CINECUT-TRIAL-');
    expect($license->expires_at)->not->toBeNull();
});

it('issues a paid license from the issuer service', function () {
    config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);

    $user = User::factory()->create();

    $result = app(LicenseIssuerService::class)->issue([
        'email' => $user->email,
        'kind' => 'paid',
        'platform' => 'mac-arm64',
        'app' => 'premiere',
    ]);

    $license = License::query()->where('user_id', $user->id)->where('kind', 'paid')->firstOrFail();

    expect($result['kind'])->toBe('paid');
    expect($result['license_key'])->toBe($license->license_key);
    expect($license->license_key)->toStartWith('LM-CINECUT-');
    expect(str_starts_with($license->license_key, 'LM-CINECUT-TRIAL-'))->toBeFalse();
    expect($license->expires_at)->toBeNull();
    expect(Entitlement::query()->where('user_id', $user->id)->where('active', true)->exists())->toBeTrue();
});
