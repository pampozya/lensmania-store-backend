<?php

namespace App\Http\Controllers;

use App\Models\StorefrontPromo;

class PromoController extends Controller
{
    public function index()
    {
        $promos = StorefrontPromo::query()
            ->where('active', true)
            ->where('code', '!=', 'TEST100')
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
            'cinecut' => $promo->price_cinecut !== null ? (float) $promo->price_cinecut : null,
        ], fn ($value): bool => $value !== null);

        $links = array_filter([
            'cinecut' => $promo->link_cinecut,
        ], fn ($value): bool => $value !== null && $value !== '');

        return [
            'label' => $promo->label,
            'affiliate' => $promo->affiliate,
            'active' => (bool) $promo->active,
            'expiresAt' => $promo->expires_at?->toIso8601String(),
            'discount_percent' => $promo->discount_percent,
            'discountPercent' => $promo->discount_percent,
            'price_cinecut' => $promo->price_cinecut !== null ? (float) $promo->price_cinecut : null,
            'fixedPrices' => $fixedPrices,
            'link_cinecut' => $promo->link_cinecut,
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
            'EARLYBIRD' => [
                'label' => 'EARLYBIRD',
                'affiliate' => null,
                'active' => true,
                'expiresAt' => null,
                'discount_percent' => null,
                'discountPercent' => null,
                'price_cinecut' => null,
                'fixedPrices' => [],
                'link_cinecut' => null,
                'links' => [
                    'cinecut' => null,
                ],
            ],
            'YOUSSEFVIP' => [
                'label' => 'Youssef VIP',
                'affiliate' => 'youssef',
                'active' => true,
                'expiresAt' => null,
                'discount_percent' => null,
                'discountPercent' => null,
                'price_cinecut' => 15.00,
                'fixedPrices' => ['cinecut' => 15],
                'link_cinecut' => null,
                'links' => [
                    'cinecut' => null,
                ],
            ],
            'NOORVIP' => [
                'label' => 'Noor VIP',
                'affiliate' => 'noor',
                'active' => true,
                'expiresAt' => null,
                'discount_percent' => null,
                'discountPercent' => null,
                'price_cinecut' => 15.00,
                'fixedPrices' => ['cinecut' => 15],
                'link_cinecut' => null,
                'links' => [
                    'cinecut' => null,
                ],
            ],
        ];
    }
}
