<?php

use App\Services\JwtService;

it('returns me details with token', function () {
    $user = \App\Models\User::factory()->create([
        'email' => 'me-user@example.com',
        'password' => 'super-secret',
    ]);

    $token = app(JwtService::class)->issue($user);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/auth/me');

    $response->assertStatus(200);
    $response->assertJson([
        'id' => $user->id,
        'email' => $user->email,
        'name' => $user->name,
    ]);
});

it('returns 401 for me when no token provided', function () {
    $this->getJson('/api/auth/me')->assertStatus(401)->assertJson(['error' => 'Unauthorized']);
});

it('logs out when token is present', function () {
    $user = \App\Models\User::factory()->create([
        'email' => 'logout-user@example.com',
        'password' => 'super-secret',
    ]);

    $token = app(JwtService::class)->issue($user);

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/auth/logout')
        ->assertStatus(200)
        ->assertJson(['message' => 'Logged out']);
});
