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

        DB::table('storefront_promos')->upsert([
            [
                'code' => 'YOUSSEF10',
                'label' => "Youssef's followers",
                'affiliate' => 'youssef',
                'discount_percent' => 10,
                'price_cinecut' => null,
                'link_cinecut' => null,
                'active' => true,
                'expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'NOOR10',
                'label' => "Noor's followers",
                'affiliate' => 'noor',
                'discount_percent' => 10,
                'price_cinecut' => null,
                'link_cinecut' => null,
                'active' => true,
                'expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'YOUSSEFVIP',
                'label' => 'Youssef VIP',
                'affiliate' => 'youssef',
                'discount_percent' => null,
                'price_cinecut' => 15.00,
                'link_cinecut' => null,
                'active' => true,
                'expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'NOORVIP',
                'label' => 'Noor VIP',
                'affiliate' => 'noor',
                'discount_percent' => null,
                'price_cinecut' => 15.00,
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
        // Keep existing promo records intact on rollback.
    }
};
