<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storefront_promos')) {
            return;
        }

        $now = now();

        // TEST100: 100% discount, no PayPal links — for testing the free-checkout flow.
        DB::table('storefront_promos')->upsert([
            [
                'code' => 'TEST100',
                'label' => 'Test 100% Discount',
                'affiliate' => 'test',
                'discount_percent' => 100,
                'price_cinecut' => null,
                'link_cinecut' => null,
                'active' => true,
                'expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['code'], [
            'label',
            'affiliate',
            'discount_percent',
            'price_cinecut',
            'link_cinecut',
            'active',
            'expires_at',
            'updated_at',
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('storefront_promos')) {
            return;
        }

        DB::table('storefront_promos')->where('code', 'TEST100')->delete();
    }
};
