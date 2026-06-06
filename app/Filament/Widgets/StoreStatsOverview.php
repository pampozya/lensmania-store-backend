<?php

namespace App\Filament\Widgets;

use App\Models\License;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStatsOverview extends BaseWidget
{
    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        // Revenue from fulfilled orders. Prefer amount_usd; fall back to amount_cents/100.
        $fulfilled = Order::query()->where('api_status', 'fulfilled')->get(['amount_usd', 'amount_cents']);
        $revenue = $fulfilled->sum(function ($o) {
            if ($o->amount_usd !== null) {
                return (float) $o->amount_usd;
            }
            return $o->amount_cents !== null ? $o->amount_cents / 100 : 0;
        });

        $salesThisMonth = Order::query()
            ->where('api_status', 'fulfilled')
            ->whereYear('purchased_at', now()->year)
            ->whereMonth('purchased_at', now()->month)
            ->count();

        $activeLicenses = License::query()->where('status', 'active')->count();

        $pending = Order::query()->where('api_status', 'paid')->count();

        return [
            Stat::make('Total Revenue', '$' . number_format($revenue, 2))
                ->description('Fulfilled orders')
                ->color('success'),

            Stat::make('Sales This Month', (string) $salesThisMonth)
                ->description(now()->format('F Y'))
                ->color('primary'),

            Stat::make('Active Licenses', (string) $activeLicenses)
                ->description('Currently active')
                ->color('info'),

            Stat::make('Pending Fulfillment', (string) $pending)
                ->description($pending > 0 ? 'Needs attention' : 'All clear')
                ->color($pending > 0 ? 'warning' : 'gray'),
        ];
    }
}
