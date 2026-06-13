<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('storefront_promos', 'price_cinecut')) {
            Schema::table('storefront_promos', function (Blueprint $table) {
                $table->decimal('price_cinecut', 8, 2)->nullable()->after('discount_percent');
            });
        }

        if (! Schema::hasColumn('storefront_promos', 'link_cinecut')) {
            Schema::table('storefront_promos', function (Blueprint $table) {
                $table->string('link_cinecut')->nullable()->after('price_cinecut');
            });
        }

        DB::table('storefront_promos')
            ->whereIn('code', ['YOUSSEFVIP', 'NOORVIP'])
            ->update([
                'price_cinecut' => 15.00,
                'link_cinecut' => null,
                'updated_at' => now(),
            ]);

        DB::table('storefront_promos')
            ->whereIn('code', ['YOUSSEF10', 'NOOR10', 'TEST100'])
            ->update([
                'price_cinecut' => null,
                'link_cinecut' => null,
                'updated_at' => now(),
            ]);

        $legacyColumns = collect(Schema::getColumnListing('storefront_promos'))
            ->filter(fn (string $column): bool => str_starts_with($column, 'price_') || str_starts_with($column, 'link_'))
            ->reject(fn (string $column): bool => in_array($column, ['price_cinecut', 'link_cinecut'], true))
            ->values()
            ->all();

        if ($legacyColumns !== []) {
            Schema::table('storefront_promos', function (Blueprint $table) use ($legacyColumns) {
                $table->dropColumn($legacyColumns);
            });
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('storefront_promos', 'price_cinecut') ? 'price_cinecut' : null,
            Schema::hasColumn('storefront_promos', 'link_cinecut') ? 'link_cinecut' : null,
        ]));

        if ($columns !== []) {
            Schema::table('storefront_promos', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
