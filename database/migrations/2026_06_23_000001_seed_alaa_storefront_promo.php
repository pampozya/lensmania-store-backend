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
                'code'             => 'ALAAVIP',
                'label'            => 'Alaa VIP',
                'affiliate'        => 'alaa',
                'discount_percent' => null,
                'price_cinecut'    => 15.00,
                'link_cinecut'     => null,
                'active'           => true,
                'expires_at'       => null,
                'created_at'       => $now,
                'updated_at'       => $now,
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
        DB::table('storefront_promos')
            ->where('code', 'ALAAVIP')
            ->delete();
    }
};
