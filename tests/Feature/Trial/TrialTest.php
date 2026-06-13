<?php

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

    $this->withHeaders(bearerFor($user))
        ->postJson('/api/trial/start')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['trial']);
});

it('expires trial status after three days', function () {
    $user = User::factory()->create();

    $this->withHeaders(bearerFor($user))->postJson('/api/trial/start')->assertCreated();

    $this->travel(4)->days();

    $this->withHeaders(bearerFor($user))
        ->getJson('/api/trial/status')
        ->assertOk()
        ->assertJsonPath('status', 'expired')
        ->assertJsonPath('active', false)
        ->assertJsonPath('allowed', false)
        ->assertJsonPath('upgrade_required', true);
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

    $this->withHeaders(bearerFor($user))
        ->getJson('/api/trial/status')
        ->assertOk()
        ->assertJsonPath('status', 'converted')
        ->assertJsonPath('paid_access', true)
        ->assertJsonPath('allowed', true)
        ->assertJsonPath('upgrade_required', false);
});
