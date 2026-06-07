<?php

namespace App\Http\Controllers;

use App\Models\StorefrontPromo;

class PromoController extends Controller
{
    public function index()
    {
        $promos = StorefrontPromo::query()
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderBy('code')
            ->get();

        if ($promos->isEmpty()) {
            return response()->json($this->fallbackPromos());
        }

        $result = [];

        foreach ($promos as $promo) {
            $result[$this->normalizeCode($promo->code)] = $this->formatPromo($promo);
        }

        return response()->json($result);
    }

    private function formatPromo(StorefrontPromo $promo): array
    {
        $fixedPrices = array_filter([
            'hushcut' => $promo->price_hushcut !== null ? (float) $promo->price_hushcut : null,
            'babelcut' => $promo->price_babelcut !== null ? (float) $promo->price_babelcut : null,
            'bundle' => $promo->price_bundle !== null ? (float) $promo->price_bundle : null,
        ], fn ($value): bool => $value !== null);

        $links = array_filter([
            'hushcut' => $promo->link_hushcut,
            'babelcut' => $promo->link_babelcut,
            'bundle' => $promo->link_bundle,
        ], fn ($value): bool => $value !== null && $value !== '');

        return [
            'label' => $promo->label,
            'affiliate' => $promo->affiliate,
            'active' => (bool) $promo->active,
            'expiresAt' => $promo->expires_at?->toIso8601String(),
            'discount_percent' => $promo->discount_percent,
            'discountPercent' => $promo->discount_percent,
            'price_hushcut' => $promo->price_hushcut !== null ? (float) $promo->price_hushcut : null,
            'price_babelcut' => $promo->price_babelcut !== null ? (float) $promo->price_babelcut : null,
            'price_bundle' => $promo->price_bundle !== null ? (float) $promo->price_bundle : null,
            'fixedPrices' => $fixedPrices,
            'link_hushcut' => $promo->link_hushcut,
            'link_babelcut' => $promo->link_babelcut,
            'link_bundle' => $promo->link_bundle,
            'links' => $links,
        ];
    }

    private function normalizeCode(?string $code): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', (string) $code));
    }

    private function fallbackPromos(): array
    {
        return [
            'YOUSSEF10' => [
                'label' => "Youssef's followers",
                'affiliate' => 'youssef',
                'active' => true,
                'expiresAt' => null,
                'discount_percent' => 10,
                'discountPercent' => 10,
                'price_hushcut' => null,
                'price_babelcut' => null,
                'price_bundle' => null,
                'fixedPrices' => [],
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/8Z3B74X38WYHY',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/J7JC4M3QU57HJ',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/FQABMZH2C7MSQ',
                'links' => [
                    'hushcut' => 'https://www.paypal.com/ncp/payment/8Z3B74X38WYHY',
                    'babelcut' => 'https://www.paypal.com/ncp/payment/J7JC4M3QU57HJ',
                    'bundle' => 'https://www.paypal.com/ncp/payment/FQABMZH2C7MSQ',
                ],
            ],
            'NOOR10' => [
                'label' => "Noor's followers",
                'affiliate' => 'noor',
                'active' => true,
                'expiresAt' => null,
                'discount_percent' => 10,
                'discountPercent' => 10,
                'price_hushcut' => null,
                'price_babelcut' => null,
                'price_bundle' => null,
                'fixedPrices' => [],
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/EZ4NVQ58B4V52',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/Q8S7KGETFWETY',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/UPYTB9N9GLVXE',
                'links' => [
                    'hushcut' => 'https://www.paypal.com/ncp/payment/EZ4NVQ58B4V52',
                    'babelcut' => 'https://www.paypal.com/ncp/payment/Q8S7KGETFWETY',
                    'bundle' => 'https://www.paypal.com/ncp/payment/UPYTB9N9GLVXE',
                ],
            ],
            'YOUSSEFVIP' => [
                'label' => 'Youssef VIP',
                'affiliate' => 'youssef',
                'active' => true,
                'expiresAt' => null,
                'discount_percent' => null,
                'discountPercent' => null,
                'price_hushcut' => '15.00',
                'price_babelcut' => '15.00',
                'price_bundle' => '25.00',
                'fixedPrices' => ['hushcut' => 15, 'babelcut' => 15, 'bundle' => 25],
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/8Z3B74X38WYHY',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/J7JC4M3QU57HJ',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/FQABMZH2C7MSQ',
                'links' => [
                    'hushcut' => 'https://www.paypal.com/ncp/payment/8Z3B74X38WYHY',
                    'babelcut' => 'https://www.paypal.com/ncp/payment/J7JC4M3QU57HJ',
                    'bundle' => 'https://www.paypal.com/ncp/payment/FQABMZH2C7MSQ',
                ],
            ],
            'NOORVIP' => [
                'label' => 'Noor VIP',
                'affiliate' => 'noor',
                'active' => true,
                'expiresAt' => null,
                'discount_percent' => null,
                'discountPercent' => null,
                'price_hushcut' => '15.00',
                'price_babelcut' => '15.00',
                'price_bundle' => '25.00',
                'fixedPrices' => ['hushcut' => 15, 'babelcut' => 15, 'bundle' => 25],
                'link_hushcut' => 'https://www.paypal.com/ncp/payment/EZ4NVQ58B4V52',
                'link_babelcut' => 'https://www.paypal.com/ncp/payment/Q8S7KGETFWETY',
                'link_bundle' => 'https://www.paypal.com/ncp/payment/UPYTB9N9GLVXE',
                'links' => [
                    'hushcut' => 'https://www.paypal.com/ncp/payment/EZ4NVQ58B4V52',
                    'babelcut' => 'https://www.paypal.com/ncp/payment/Q8S7KGETFWETY',
                    'bundle' => 'https://www.paypal.com/ncp/payment/UPYTB9N9GLVXE',
                ],
            ],
        ];
    }
}
