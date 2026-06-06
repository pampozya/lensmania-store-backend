<?php

namespace Database\Seeders;

use App\Models\BundleItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $hushcut = Product::firstOrCreate(
            ['slug' => 'hushcut'],
            ['name' => 'HushCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true]
        );

        $babelcut = Product::firstOrCreate(
            ['slug' => 'babelcut'],
            ['name' => 'BabelCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true]
        );

        $bundle = Product::firstOrCreate(
            ['slug' => 'bundle'],
            ['name' => 'Studio Pass', 'price_cents' => 5000, 'is_bundle' => true, 'active' => true]
        );

        BundleItem::firstOrCreate([
            'bundle_product_id' => $bundle->id,
            'item_product_id' => $hushcut->id,
        ]);

        BundleItem::firstOrCreate([
            'bundle_product_id' => $bundle->id,
            'item_product_id' => $babelcut->id,
        ]);
    }
}
