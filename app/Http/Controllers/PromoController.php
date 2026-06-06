<?php

namespace App\Http\Controllers;

use App\Models\StorefrontPromo;
use Illuminate\Support\Facades\Cache;

class PromoController extends Controller
{
    public function index()
    {
        return Cache::remember('storefront_promos', 5 * 60, function () {
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

            return $result;
        });
    }
}
