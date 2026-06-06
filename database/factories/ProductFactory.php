<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->word(),
            'name' => fake()->words(2, true),
            'price_cents' => 3500,
            'is_bundle' => false,
            'active' => true,
        ];
    }
}

