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
                'price_hushcut' => null,
                'price_babelcut' => null,
                'price_bundle' => null,
                'link_hushcut' => null,
                'link_babelcut' => null,
                'link_bundle' => null,
                'active' => true,
                'expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['code'], [
            'label',
            'affiliate',
            'discount_percent',
            'price_hushcut',
            'price_babelcut',
            'price_bundle',
            'link_hushcut',
            'link_babelcut',
            'link_bundle',
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
