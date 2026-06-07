<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class PromoAnalyticsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $orders = $this->paidOrders();
        $promoOrders = $orders->filter(fn (Order $order): bool => filled($order->promo_code));
        $vipOrders = $orders->filter(fn (Order $order): bool => in_array(strtoupper((string) $order->promo_code), ['YOUSSEFVIP', 'NOORVIP'], true));
        $topPromo = $promoOrders
            ->groupBy(fn (Order $order): string => strtoupper((string) $order->promo_code))
            ->map(fn (Collection $group): float => $group->sum(fn (Order $order): float => $this->amount($order)))
            ->sortDesc()
            ->keys()
            ->first();

        return [
            Stat::make('Promo Revenue', '$' . number_format($promoOrders->sum(fn (Order $order): float => $this->amount($order)), 2))
                ->description($promoOrders->count() . ' paid promo order(s)')
                ->color('success'),

            Stat::make('Top Promo', $topPromo ?: 'None yet')
                ->description('Best by paid revenue')
                ->color($topPromo ? 'primary' : 'gray'),

            Stat::make('VIP Orders', (string) $vipOrders->count())
                ->description('YOUSSEFVIP / NOORVIP')
                ->color($vipOrders->count() > 0 ? 'warning' : 'gray'),

            Stat::make('No-Promo Orders', (string) ($orders->count() - $promoOrders->count()))
                ->description('Useful baseline for offer lift')
                ->color('info'),
        ];
    }

    private function paidOrders(): Collection
    {
        return Order::query()
            ->where(function ($query): void {
                $query->where('status', 'paid')
                    ->orWhereIn('api_status', ['paid', 'fulfilled']);
            })
            ->get();
    }

    private function amount(Order $order): float
    {
        if ($order->amount_usd !== null) {
            return (float) $order->amount_usd;
        }

        return $order->amount_cents !== null ? ((int) $order->amount_cents) / 100 : 0;
    }
}
