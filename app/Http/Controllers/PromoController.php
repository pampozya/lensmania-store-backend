<?php

namespace App\Http\Controllers;

use App\Models\StorefrontPromo;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PromoController extends Controller
{
    public function index()
    {
        return Cache::remember('storefront_promos', 5 * 60, function () {
            try {
                $promos = StorefrontPromo::where('active', true)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>=', now());
                    })
                    ->get();

                $result = [];
                foreach ($promos as $promo) {
                    $result[$promo->code] = [
                        'label' => $promo->label,
                        'affiliate' => $promo->affiliate,
                        'discount_percent' => $promo->discount_percent,
                        'price_hushcut' => $promo->price_hushcut,
                        'price_babelcut' => $promo->price_babelcut,
                        'price_bundle' => $promo->price_bundle,
                        'link_hushcut' => $promo->link_hushcut,
                        'link_babelcut' => $promo->link_babelcut,
                        'link_bundle' => $promo->link_bundle,
                    ];
                }

                if ($result !== []) {
                    return $result;
                }
            } catch (Throwable) {
                return $this->fallbackPromos();
            }

            return $this->fallbackPromos();
        });
    }

    private function fallbackPromos(): array
    {
        return [
            'YOUSSEF10' => [
                'label' => "Youssef's followers",
                'affiliate' => 'youssef',
                'discount_percent' => 10,
                'price_hushcut' => null,
                'price_babelcut' => null,
                'price_bundle' => null,
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/8Z3B74X38WYHY',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/J7JC4M3QU57HJ',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/FQABMZH2C7MSQ',
            ],
            'NOOR10' => [
                'label' => "Noor's followers",
                'affiliate' => 'noor',
                'discount_percent' => 10,
                'price_hushcut' => null,
                'price_babelcut' => null,
                'price_bundle' => null,
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/EZ4NVQ58B4V52',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/Q8S7KGETFWETY',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/UPYTB9N9GLVXE',
            ],
            'YOUSSEFVIP' => [
                'label' => 'Youssef VIP',
                'affiliate' => 'youssef',
                'discount_percent' => null,
                'price_hushcut' => '15.00',
                'price_babelcut' => '15.00',
                'price_bundle' => '25.00',
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/8Z3B74X38WYHY',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/J7JC4M3QU57HJ',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/FQABMZH2C7MSQ',
            ],
            'NOORVIP' => [
                'label' => 'Noor VIP',
                'affiliate' => 'noor',
                'discount_percent' => null,
                'price_hushcut' => '15.00',
                'price_babelcut' => '15.00',
                'price_bundle' => '25.00',
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/EZ4NVQ58B4V52',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/Q8S7KGETFWETY',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/UPYTB9N9GLVXE',
            ],
        ];
    }
}
