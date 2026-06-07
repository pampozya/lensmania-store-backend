<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\SiteEvent;
use Filament\Pages\Page;

final class PromoLeaderboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Promo Leaderboard';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?string $title = 'Promo Code Leaderboard';
    protected static string $view = 'filament.pages.analytics-report';

    public function getCards(): array
    {
        $promoApplies = SiteEvent::query()->where('name', 'promo_applied')->count();
        $promoClicks = SiteEvent::query()->where('name', 'buy_clicked')->whereNotNull('promo_code')->count();
        $promoOrders = Order::query()->whereNotNull('promo_code')->where(function ($query): void {
            $query->where('status', 'paid')->orWhereIn('api_status', ['paid', 'fulfilled']);
        })->get();

        return [
            ['label' => 'Promo applies', 'value' => (string) $promoApplies, 'hint' => 'All tracked codes'],
            ['label' => 'Promo buy clicks', 'value' => (string) $promoClicks, 'hint' => 'Buy button after code'],
            ['label' => 'Promo sales', 'value' => (string) $promoOrders->count(), 'hint' => 'Paid/fulfilled orders'],
            ['label' => 'Promo revenue', 'value' => '$' . number_format($promoOrders->sum(fn (Order $order): float => $this->amount($order)), 2), 'hint' => 'Paid/fulfilled orders'],
        ];
    }

    public function getSections(): array
    {
        $codes = collect(['YOUSSEF10', 'NOOR10', 'YOUSSEFVIP', 'NOORVIP'])
            ->merge(SiteEvent::query()->whereNotNull('promo_code')->distinct()->pluck('promo_code'))
            ->merge(Order::query()->whereNotNull('promo_code')->distinct()->pluck('promo_code'))
            ->filter()
            ->map(fn ($code): string => strtoupper((string) $code))
            ->unique()
            ->values();

        $rows = $codes->map(function (string $code): array {
            $applies = SiteEvent::query()->where('name', 'promo_applied')->where('promo_code', $code)->count();
            $clicks = SiteEvent::query()->where('name', 'buy_clicked')->where('promo_code', $code)->count();
            $orders = Order::query()->where('promo_code', $code)->where(function ($query): void {
                $query->where('status', 'paid')->orWhereIn('api_status', ['paid', 'fulfilled']);
            })->get();
            $sales = $orders->count();

            return [
                $code,
                (string) $applies,
                (string) $clicks,
                (string) $sales,
                '$' . number_format($orders->sum(fn (Order $order): float => $this->amount($order)), 2),
                $clicks > 0 ? number_format(($sales / $clicks) * 100, 1) . '%' : '0.0%',
            ];
        })->all();

        return [[
            'title' => 'Promo performance',
            'description' => 'Compares code applies, checkout clicks, paid sales, revenue, and click-to-sale conversion.',
            'columns' => ['Code', 'Applies', 'Buy clicks', 'Sales', 'Revenue', 'Click conversion'],
            'rows' => $rows,
        ]];
    }

    private function amount(Order $order): float
    {
        return $order->amount_usd !== null ? (float) $order->amount_usd : (((int) $order->amount_cents) / 100);
    }
}
