<?php

use App\Models\User;

it('renders the license issuer page for admins', function () {
    config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/license-issuer')
        ->assertOk()
        ->assertSee('Issue a CineCut license');
});
