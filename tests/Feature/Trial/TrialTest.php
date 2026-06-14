<?php

use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\Trial;
use App\Models\User;
use App\Services\FulfillmentService;
use App\Services\JwtService;
use Database\Seeders\ProductSeeder;

function bearerFor(User $user): array
{
    return ['Authorization' => 'Bearer ' . app(JwtService::class)->issue($user)];
}

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    $this->seed(ProductSeeder::class);
});

it('requires authentication before starting a trial', function () {
    $this->postJson('/api/trial/start')->assertUnauthorized();
});

it('returns not started trial status before activation', function () {
    $user = User::factory()->create();

    $this->withHeaders(bearerFor($user))
        ->getJson('/api/trial/status')
        ->assertOk()
        ->assertJsonPath('status', 'not_started')
        ->assertJsonPath('jobs_limit', 3)
        ->assertJsonPath('minutes_limit', 60)
        ->assertJsonPath('upgrade_required', true);
});

it('starts one three day trial per user', function () {
    $user = User::factory()->create();

    $this->withHeaders(bearerFor($user))
        ->postJson('/api/trial/start', [
            'device_id' => 'mac-001',
            'platform' => 'mac',
            'app_version' => '1.0.0',
        ])
        ->assertCreated()
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('active', true)
        ->assertJsonPath('allowed', true)
        ->assertJsonPath('device_id', 'mac-001')
        ->assertJsonPath('jobs_remaining', 3)
        ->assertJsonPath('minutes_remaining', 60)
        ->assertJsonPath('download_url', config('downloads.products.cinecut.url'));

    $trial = Trial::query()->where('user_id', $user->id)->firstOrFail();
    expect($trial->started_at->diffInHours($trial->expires_at))->toEqual(72);

    $this->assertDatabaseHas('licenses', [
        'user_id' => $user->id,
        'product_id' => Product::query()->where('slug', 'cinecut')->value('id'),
        'kind' => 'trial',
        'status' => 'active',
    ]);
    $this->assertDatabaseMissing('licenses', [
        'user_id' => $user->id,
        'product_id' => Product::query()->where('slug', 'cinecut')->value('id'),
        'kind' => 'paid',
    ]);

    $this->withHeaders(bearerFor($user))
        ->postJson('/api/trial/start')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['trial']);
});

it('issues only a trial licence for trial access', function () {
    $user = User::factory()->create();
    $product = Product::query()->where('slug', 'cinecut')->firstOrFail();

    $response = $this->withHeaders(bearerFor($user))
        ->postJson('/api/trial/start', [
            'device_id' => 'trial-device-001',
            'platform' => 'mac-arm64',
            'app_version' => '0.2.0',
        ])
        ->assertCreated()
        ->assertJsonPath('license_kind', 'trial');

    $trial = Trial::query()->where('user_id', $user->id)->firstOrFail();
    $license = License::query()
        ->where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect($license->kind)->toBe('trial');
    expect($license->status)->toBe('active');
    expect(str_starts_with($license->license_key, 'LM-CINECUT-TRIAL-'))->toBeTrue();
    expect($license->expires_at?->toIso8601String())->toBe($trial->expires_at?->toIso8601String());
    expect($response->json('license_key'))->toBe($license->license_key);

    expect(License::query()
        ->where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->where('kind', 'paid')
        ->exists())->toBeFalse();

    $this->withHeaders(bearerFor($user))
        ->getJson('/api/auth/orders')
        ->assertOk()
        ->assertJsonPath('0.license_kind', 'trial')
        ->assertJsonPath('0.licenses.0.license_kind', 'trial');
});

it('expires trial status after three days', function () {
    $user = User::factory()->create();

    $licenseKey = $this->withHeaders(bearerFor($user))
        ->postJson('/api/trial/start', [
            'device_id' => 'mac-001',
            'platform' => 'mac-arm64',
            'app_version' => '0.2.0',
        ])
        ->assertCreated()
        ->json('license_key');

    $this->travel(4)->days();

    $this->withHeaders(bearerFor($user))
        ->getJson('/api/trial/status')
        ->assertOk()
        ->assertJsonPath('status', 'expired')
        ->assertJsonPath('active', false)
        ->assertJsonPath('allowed', false)
        ->assertJsonPath('upgrade_required', true);

    $this->postJson('/api/license/activate', [
        'license_key' => $licenseKey,
        'device_id' => 'mac-001',
        'platform' => 'mac-arm64',
        'app_version' => '0.2.0',
    ])
        ->assertOk()
        ->assertJsonPath('allowed', false)
        ->assertJsonPath('message', 'License not found or inactive.');

    $this->assertDatabaseHas('licenses', [
        'user_id' => $user->id,
        'kind' => 'trial',
        'status' => 'revoked',
    ]);
});

it('reaches limit after three consumed jobs', function () {
    $user = User::factory()->create();

    $this->withHeaders(bearerFor($user))->postJson('/api/trial/start')->assertCreated();

    for ($i = 1; $i <= 3; $i++) {
        $response = $this->withHeaders(bearerFor($user))
            ->postJson('/api/trial/consume', [
                'device_id' => 'mac-001',
                'minutes_processed' => 5,
            ])
            ->assertOk();
    }

    $response
        ->assertJsonPath('status', 'limit_reached')
        ->assertJsonPath('active', false)
        ->assertJsonPath('allowed', false)
        ->assertJsonPath('jobs_used', 3)
        ->assertJsonPath('jobs_remaining', 0)
        ->assertJsonPath('upgrade_required', true);
});

it('reaches limit after sixty processed minutes', function () {
    $user = User::factory()->create();

    $this->withHeaders(bearerFor($user))->postJson('/api/trial/start')->assertCreated();

    $this->withHeaders(bearerFor($user))
        ->postJson('/api/trial/consume', [
            'device_id' => 'mac-001',
            'minutes_processed' => 60,
        ])
        ->assertOk()
        ->assertJsonPath('status', 'limit_reached')
        ->assertJsonPath('minutes_used', 60)
        ->assertJsonPath('minutes_remaining', 0)
        ->assertJsonPath('upgrade_required', true);
});

it('locks trial consumption to one device', function () {
    $user = User::factory()->create();

    $this->withHeaders(bearerFor($user))
        ->postJson('/api/trial/start', ['device_id' => 'mac-001'])
        ->assertCreated();

    $this->withHeaders(bearerFor($user))
        ->postJson('/api/trial/consume', [
            'device_id' => 'mac-002',
            'minutes_processed' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['device_id']);
});

it('marks trial converted after paid fulfillment', function () {
    $user = User::factory()->create();
    $product = Product::query()->where('slug', 'cinecut')->firstOrFail();

    $this->withHeaders(bearerFor($user))->postJson('/api/trial/start')->assertCreated();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'product_slug' => 'cinecut',
        'amount_cents' => 3500,
        'amount_usd' => 35.00,
        'status' => 'paid',
        'api_status' => 'paid',
    ]);

    app(FulfillmentService::class)->fulfillStaticOrder($order);

    $trialLicense = License::query()
        ->where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->where('kind', 'trial')
        ->firstOrFail();

    $paidLicense = License::query()
        ->where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->where('kind', 'paid')
        ->firstOrFail();

    expect($trialLicense->license_key)->toStartWith('LM-CINECUT-TRIAL-');
    expect($paidLicense->license_key)->toStartWith('LM-CINECUT-');
    expect(str_starts_with($paidLicense->license_key, 'LM-CINECUT-TRIAL-'))->toBeFalse();
    expect($paidLicense->expires_at)->toBeNull();

    $this->withHeaders(bearerFor($user))
        ->getJson('/api/trial/status')
        ->assertOk()
        ->assertJsonPath('status', 'converted')
        ->assertJsonPath('paid_access', true)
        ->assertJsonPath('allowed', true)
        ->assertJsonPath('upgrade_required', false);

    $orders = $this->withHeaders(bearerFor($user))
        ->getJson('/api/auth/orders')
        ->assertOk()
        ->json();

    $trial = Trial::query()->where('user_id', $user->id)->firstOrFail();
    $paidOrder = collect($orders)->firstWhere('id', $order->id);
    $trialOrder = collect($orders)->firstWhere('id', 'trial-' . $trial->id);

    expect($paidOrder)->not->toBeNull();
    expect($trialOrder)->not->toBeNull();
    expect($paidOrder['license_kind'])->toBe('paid');
    expect($paidOrder['licenses'][0]['license_kind'])->toBe('paid');
    expect($paidOrder['license_key'])->toBe($paidLicense->license_key);
    expect($trialOrder['license_kind'])->toBe('trial');
    expect($trialOrder['licenses'][0]['license_kind'])->toBe('trial');
});
