<?php

namespace Database\Factories;

use App\Models\PriceQuote;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceQuoteFactory extends Factory
{
    protected $model = PriceQuote::class;

    public function definition(): array
    {
        $product = Product::factory()->create();

        return [
            'quote_token' => fake()->uuid(),
            'user_id' => User::factory(),
            'product_id' => $product->id,
            'base_cents' => 3500,
            'discount_cents' => 0,
            'amount_cents' => 3500,
            'currency' => 'USD',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
            'promo_code_id' => null,
            'affiliate_id' => null,
        ];
    }
}

