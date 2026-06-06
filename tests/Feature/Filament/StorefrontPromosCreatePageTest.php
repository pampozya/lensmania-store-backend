<?php

use App\Models\User;

it('renders the storefront promo create page for admins', function () {
    config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/storefront-promos/create')
        ->assertOk();
});
