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

        DB::table('storefront_promos')
            ->where('code', 'TEST100')
            ->update([
                'active' => false,
                'link_hushcut' => null,
                'link_babelcut' => null,
                'link_bundle' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('storefront_promos')) {
            return;
        }

        DB::table('storefront_promos')
            ->where('code', 'TEST100')
            ->update([
                'active' => true,
                'updated_at' => now(),
            ]);
    }
};
