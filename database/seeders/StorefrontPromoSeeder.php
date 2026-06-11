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
                'price_hushcut' => null,
                'price_babelcut' => null,
                'price_bundle' => null,
                'link_hushcut' => null,
                'link_babelcut' => null,
                'link_bundle' => null,
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
                'price_hushcut' => null,
                'price_babelcut' => null,
                'price_bundle' => null,
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/8Z3B74X38WYHY',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/J7JC4M3QU57HJ',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/FQABMZH2C7MSQ',
                'active' => true,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'NOOR10',
                'label' => "Noor's followers",
                'affiliate' => 'noor',
                'discount_percent' => 10,
                'price_hushcut' => null,
                'price_babelcut' => null,
                'price_bundle' => null,
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/EZ4NVQ58B4V52',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/Q8S7KGETFWETY',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/UPYTB9N9GLVXE',
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
                'price_hushcut' => 15.00,
                'price_babelcut' => 15.00,
                'price_bundle' => 25.00,
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/8Z3B74X38WYHY',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/J7JC4M3QU57HJ',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/FQABMZH2C7MSQ',
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
                'price_hushcut' => 15.00,
                'price_babelcut' => 15.00,
                'price_bundle' => 25.00,
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/EZ4NVQ58B4V52',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/Q8S7KGETFWETY',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/UPYTB9N9GLVXE',
                'active' => true,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['code'], ['affiliate', 'discount_percent', 'price_hushcut', 'price_babelcut', 'price_bundle', 'link_hushcut', 'link_babelcut', 'link_bundle', 'active', 'expires_at', 'updated_at']);
    }
}
