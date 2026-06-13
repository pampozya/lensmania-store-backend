<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        DB::table('products')->updateOrInsert(
            ['slug' => 'cinecut'],
            [
                'name' => 'CineCut',
                'price_cents' => 3500,
                'is_bundle' => false,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('products')
            ->where('slug', '!=', 'cinecut')
            ->update(['active' => false, 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('products')
            ->where('slug', 'cinecut')
            ->update(['active' => false, 'updated_at' => now()]);
    }
};
