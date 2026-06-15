<?php

namespace Database\Seeders;

use App\Models\StorefrontPromo;
use Illuminate\Database\Seeder;

class StorefrontPromoSeeder extends Seeder
{
    public function run(): void
    {
        StorefrontPromo::upsert([
            [
                // Production kill-switch: keep the test code record, but never expose it.
                'code' => 'TEST100',
                'label' => 'Test 100% Discount',
                'affiliate' => 'test',
                'discount_percent' => 100,
                'price_cinecut' => null,
                'link_cinecut' => null,
                'active' => false,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'YOUSSEF10',
                'label' => "Youssef's followers",
                'affiliate' => 'youssef',
                'discount_percent' => 10,
                'price_cinecut' => null,
                'link_cinecut' => null,
                'active' => false,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'NOOR10',
                'label' => "Noor's followers",
                'affiliate' => 'noor',
                'discount_percent' => 10,
                'price_cinecut' => null,
                'link_cinecut' => null,
                'active' => false,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EARLYBIRD',
                'label' => 'EARLYBIRD',
                'affiliate' => null,
                'discount_percent' => null,
                'price_cinecut' => 15.00,
                'link_cinecut' => null,
                'active' => true,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'YOUSSEFVIP',
                'label' => 'Youssef VIP',
                'affiliate' => 'youssef',
                'discount_percent' => null,
                'price_cinecut' => 15.00,
                'link_cinecut' => null,
                'active' => true,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'NOORVIP',
                'label' => 'Noor VIP',
                'affiliate' => 'noor',
                'discount_percent' => null,
                'price_cinecut' => 15.00,
                'link_cinecut' => null,
                'active' => true,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['code'], ['affiliate', 'discount_percent', 'price_cinecut', 'link_cinecut', 'active', 'expires_at', 'updated_at']);
    }
}
