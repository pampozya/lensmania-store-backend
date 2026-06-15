<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // EARLYBIRD launched active but with no discount configured, so the
        // storefront showed "applied" yet kept the full $35 price. Set it to the
        // $15 public launch price (matching the VIP affiliate codes).
        DB::table('storefront_promos')
            ->where('code', 'EARLYBIRD')
            ->update([
                'price_cinecut' => 15.00,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('storefront_promos')
            ->where('code', 'EARLYBIRD')
            ->update([
                'price_cinecut' => null,
                'updated_at' => now(),
            ]);
    }
};
