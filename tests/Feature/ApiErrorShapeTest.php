<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('returns 401 (not 400) for unauthenticated protected endpoints', function () {
    $this->seed(\Database\Seeders\ProductSeeder::class);
    $this->postJson('/api/checkout/quote', ['product_slug' => 'cinecut'])
        ->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('returns 422 with field errors for invalid input', function () {
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user)
        ->postJson('/api/checkout/quote', ['product_slug' => 'notreal'])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['product_slug']]);
});
