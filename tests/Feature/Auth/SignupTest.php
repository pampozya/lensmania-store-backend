<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('signup with valid email and password returns token', function () {
    $email = 'new-user@example.com';

    $response = $this->postJson('/api/auth/signup', [
        'name' => 'New User',
        'email' => $email,
        'password' => 'super-secret',
        'password_confirmation' => 'super-secret',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'token',
        'user' => ['id', 'email'],
    ]);

    $this->assertDatabaseHas('users', [
        'email' => $email,
        'name' => 'New User',
    ]);
});

it('stores a hashed password on signup', function () {
    $this->postJson('/api/auth/signup', [
        'name' => 'Hashed User',
        'email' => 'hashed-user@example.com',
        'password' => 'super-secret',
        'password_confirmation' => 'super-secret',
    ])->assertStatus(201);

    $user = User::query()->where('email', 'hashed-user@example.com')->firstOrFail();

    expect(Hash::check('super-secret', (string) $user->password))->toBeTrue();
    expect($user->password)->not->toBe('super-secret');
});

it('signup with duplicate email returns 422', function () {
    User::factory()->create(['email' => 'existing-user@example.com']);

    $response = $this->postJson('/api/auth/signup', [
        'name' => 'Existing User',
        'email' => 'existing-user@example.com',
        'password' => 'super-secret',
        'password_confirmation' => 'super-secret',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
    $response->assertJsonFragment(['errors' => ['email' => ['Email already exists']]]);
});

it('signup with short password returns 422', function () {
    $response = $this->postJson('/api/auth/signup', [
        'name' => 'Short Password User',
        'email' => 'shortpass@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('password');
    $response->assertJsonFragment(['errors' => ['password' => ['Password too short']]]);
});
