<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regression: the Filament create/edit forms must render. A non-existent
 * Filament method (e.g. ->uppercase()) silently 500s the page, so assert
 * the admin create page loads.
 */
class StorefrontPromoCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_load_storefront_promo_create_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/storefront-promos/create')
            ->assertSuccessful();
    }
}
