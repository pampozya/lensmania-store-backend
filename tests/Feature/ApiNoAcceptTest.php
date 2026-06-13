<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('returns 401 for unauth api call even without Accept header', function () {
    $this->seed(\Database\Seeders\ProductSeeder::class);
    // Simulate a plain POST with no Accept: application/json
    $r = $this->call('POST', '/api/checkout/quote', ['product_slug' => 'cinecut']);
    expect($r->status())->toBe(401);
});
