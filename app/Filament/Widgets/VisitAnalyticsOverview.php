<?php

namespace App\Filament\Widgets;

use App\Models\SiteVisit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisitAnalyticsOverview extends BaseWidget
{
    protected static ?int $sort = -4;

    protected function getStats(): array
    {
        $today = SiteVisit::query()->whereDate('created_at', now()->toDateString());
        $topCountry = SiteVisit::query()
            ->selectRaw("coalesce(nullif(country, ''), 'Unknown') as label, count(*) as total")
            ->groupByRaw("coalesce(nullif(country, ''), 'Unknown')")
            ->orderByDesc('total')
            ->value('label');
        $topDevice = SiteVisit::query()
            ->selectRaw("coalesce(nullif(device_type, ''), 'unknown') as label, count(*) as total")
            ->groupByRaw("coalesce(nullif(device_type, ''), 'unknown')")
            ->orderByDesc('total')
            ->value('label');

        return [
            Stat::make('Total Visits', (string) SiteVisit::query()->count())
                ->description('Tracked page visits')
                ->color('primary'),

            Stat::make('Unique Visitors', (string) SiteVisit::query()->whereNotNull('visitor_hash')->distinct('visitor_hash')->count('visitor_hash'))
                ->description('Hashed session count')
                ->color('info'),

            Stat::make('Visits Today', (string) $today->count())
                ->description(now()->format('M j, Y'))
                ->color('success'),

            Stat::make('Top Location / Device', ($topCountry ?: 'Unknown') . ' / ' . ($topDevice ?: 'unknown'))
                ->description('Highest visit segments')
                ->color('warning'),
        ];
    }
}
