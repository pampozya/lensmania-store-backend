<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $cinecutId = DB::table('products')->where('slug', 'cinecut')->value('id');
        if (! $cinecutId) {
            return;
        }

        $retiredProductIds = DB::table('products')
            ->where('slug', '!=', 'cinecut')
            ->pluck('id');

        if ($retiredProductIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($cinecutId, $retiredProductIds): void {
            if (Schema::hasTable('bundle_items')) {
                DB::table('bundle_items')->delete();
            }

            if (Schema::hasTable('orders')) {
                $orderUpdates = [
                    'product_id' => $cinecutId,
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('orders', 'product_slug')) {
                    $orderUpdates['product_slug'] = 'cinecut';
                }
                if (Schema::hasColumn('orders', 'product_name')) {
                    $orderUpdates['product_name'] = 'CineCut';
                }

                DB::table('orders')
                    ->whereIn('product_id', $retiredProductIds)
                    ->update($orderUpdates);
            }

            if (Schema::hasTable('builds')) {
                DB::table('builds')
                    ->whereIn('product_id', $retiredProductIds)
                    ->delete();
            }

            if (Schema::hasTable('promo_codes')) {
                DB::table('promo_codes')
                    ->whereIn('product_id', $retiredProductIds)
                    ->update(['product_id' => null]);
            }

            foreach (['licenses', 'entitlements', 'price_quotes', 'trials'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                if ($table === 'entitlements') {
                    DB::table('entitlements')
                        ->whereIn('id', function ($query) use ($cinecutId, $retiredProductIds): void {
                            $query->select('retired.id')
                                ->from('entitlements as retired')
                                ->join('entitlements as cinecut', function ($join) use ($cinecutId): void {
                                    $join->on('cinecut.user_id', '=', 'retired.user_id')
                                        ->on('cinecut.order_id', '=', 'retired.order_id')
                                        ->where('cinecut.product_id', '=', $cinecutId);
                                })
                                ->whereIn('retired.product_id', $retiredProductIds);
                        })
                        ->delete();
                }

                DB::table($table)
                    ->whereIn('product_id', $retiredProductIds)
                    ->update(['product_id' => $cinecutId]);
            }

            DB::table('products')
                ->whereIn('id', $retiredProductIds)
                ->delete();
        });
    }

    public function down(): void
    {
        // Product consolidation is intentionally one-way.
    }
};
