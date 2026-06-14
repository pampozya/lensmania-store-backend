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

        DB::table('storefront_promos')
            ->whereIn('code', ['TEST100', 'NOOR10', 'YOUSSEF10'])
            ->update([
                'active' => false,
                'updated_at' => $now,
            ]);

        DB::table('storefront_promos')->upsert([
            [
                'code' => 'EARLYBIRD',
                'label' => 'EARLYBIRD',
                'affiliate' => null,
                'discount_percent' => null,
                'price_cinecut' => null,
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

        DB::table('storefront_promos')
            ->whereIn('code', ['NOOR10', 'YOUSSEF10'])
            ->update([
                'active' => true,
                'updated_at' => now(),
            ]);
    }
};
