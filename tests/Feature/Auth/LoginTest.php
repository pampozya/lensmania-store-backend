<?php

use App\Models\User;

it('login with valid credentials returns token', function () {
    User::factory()->create([
        'email' => 'login-user@example.com',
        'password' => 'super-secret',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'login-user@example.com',
        'password' => 'super-secret',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'email'],
        ]);
});

it('login with wrong password returns 401', function () {
    User::factory()->create([
        'email' => 'wrong-pass@example.com',
        'password' => 'super-secret',
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'wrong-pass@example.com',
        'password' => 'not-right',
    ])->assertStatus(401)->assertJson(['error' => 'Invalid credentials']);
});

it('login with non-existent email returns 401', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'missing-user@example.com',
        'password' => 'anything123',
    ])->assertStatus(401)->assertJson(['error' => 'Invalid credentials']);
});
