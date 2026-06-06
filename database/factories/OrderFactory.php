<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $product = Product::factory()->create();

        return [
            'user_id' => User::factory(),
            'product_id' => $product->id,
            'product_slug' => $product->slug,
            'amount_cents' => 3500,
            'amount_usd' => 35.00,
            'currency' => 'USD',
            'status' => 'created',
            'api_status' => 'pending',
            'promo_code' => null,
            'license_key' => null,
            'download_url' => null,
            'purchased_at' => null,
        ];
    }
}
