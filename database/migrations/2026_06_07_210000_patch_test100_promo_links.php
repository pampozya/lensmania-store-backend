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

        $updates = ['updated_at' => now()];
        if (Schema::hasColumn('storefront_promos', 'link_cinecut')) {
            $updates['link_cinecut'] = null;
        }

        DB::table('storefront_promos')
            ->where('code', 'TEST100')
            ->update($updates);
    }

    public function down(): void
    {
        if (! Schema::hasTable('storefront_promos')) {
            return;
        }

        $updates = ['updated_at' => now()];
        if (Schema::hasColumn('storefront_promos', 'link_cinecut')) {
            $updates['link_cinecut'] = null;
        }

        DB::table('storefront_promos')
            ->where('code', 'TEST100')
            ->update($updates);
    }
};
