<?php

use Illuminate\Support\Facades\DB;

it('deduplicates entitlements before consolidating retired products into CineCut', function () {
    $now = now();

    DB::table('products')->updateOrInsert(['slug' => 'cinecut'], [
        'slug' => 'cinecut',
        'name' => 'CineCut',
        'price_cents' => 3500,
        'is_bundle' => false,
        'active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cinecutId = DB::table('products')->where('slug', 'cinecut')->value('id');

    DB::table('products')->updateOrInsert(['slug' => 'hushcut'], [
        'slug' => 'hushcut',
        'name' => 'HushCut',
        'price_cents' => 3500,
        'is_bundle' => false,
        'active' => false,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $legacyAId = DB::table('products')->where('slug', 'hushcut')->value('id');

    DB::table('products')->updateOrInsert(['slug' => 'babelcut'], [
        'slug' => 'babelcut',
        'name' => 'BabelCut',
        'price_cents' => 3500,
        'is_bundle' => false,
        'active' => false,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $legacyBId = DB::table('products')->where('slug', 'babelcut')->value('id');

    $userId = DB::table('users')->insertGetId([
        'name' => 'Migration Fixture',
        'email' => 'migration-fixture@example.com',
        'password' => bcrypt('password'),
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $orderWithExistingCinecut = DB::table('orders')->insertGetId([
        'user_id' => $userId,
        'product_id' => $legacyAId,
        'amount_cents' => 3500,
        'amount_usd' => 35.00,
        'currency' => 'USD',
        'status' => 'paid',
        'api_status' => 'paid',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $orderWithOnlyRetiredProducts = DB::table('orders')->insertGetId([
        'user_id' => $userId,
        'product_id' => $legacyBId,
        'amount_cents' => 3500,
        'amount_usd' => 35.00,
        'currency' => 'USD',
        'status' => 'paid',
        'api_status' => 'paid',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    foreach ([
        [$orderWithExistingCinecut, $cinecutId],
        [$orderWithExistingCinecut, $legacyAId],
        [$orderWithExistingCinecut, $legacyBId],
        [$orderWithOnlyRetiredProducts, $legacyAId],
        [$orderWithOnlyRetiredProducts, $legacyBId],
    ] as [$orderId, $productId]) {
        DB::table('entitlements')->insert([
            'user_id' => $userId,
            'product_id' => $productId,
            'order_id' => $orderId,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $migration = require database_path('migrations/2026_06_13_203000_consolidate_products_to_cinecut.php');
    $migration->up();

    expect(DB::table('entitlements')->count())->toBe(2)
        ->and(DB::table('entitlements')->where('product_id', $cinecutId)->count())->toBe(2)
        ->and(DB::table('entitlements')->whereIn('product_id', [$legacyAId, $legacyBId])->count())->toBe(0)
        ->and(DB::table('orders')->whereIn('product_id', [$legacyAId, $legacyBId])->count())->toBe(0)
        ->and(DB::table('products')->whereIn('id', [$legacyAId, $legacyBId])->exists())->toBeFalse();
});
