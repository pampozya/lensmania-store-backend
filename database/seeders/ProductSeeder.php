<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::updateOrCreate(
            ['slug' => 'cinecut'],
            ['name' => 'CineCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true]
        );

        Product::query()
            ->where('slug', '!=', 'cinecut')
            ->update(['active' => false]);
    }
}
