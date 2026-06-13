<?php

namespace App\Filament\Widgets;

use App\Models\Trial;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrialAnalyticsOverview extends BaseWidget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    public static function trialCounts(): array
    {
        $total = Trial::query()->count();

        $active = Trial::query()
            ->whereNull('converted_at')
            ->where('status', 'active')
            ->where('expires_at', '>=', now())
            ->whereColumn('jobs_used', '<', 'jobs_limit')
            ->whereColumn('minutes_used', '<', 'minutes_limit')
            ->count();

        $ended = Trial::query()
            ->whereNull('converted_at')
            ->where('status', '!=', 'converted')
            ->where(function ($query): void {
                $query->whereIn('status', ['expired', 'limit_reached'])
                    ->orWhere('expires_at', '<', now())
                    ->orWhereColumn('jobs_used', '>=', 'jobs_limit')
                    ->orWhereColumn('minutes_used', '>=', 'minutes_limit');
            })
            ->count();

        $converted = Trial::query()
            ->where(function ($query): void {
                $query->where('status', 'converted')
                    ->orWhereNotNull('converted_at');
            })
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'ended' => $ended,
            'converted' => $converted,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0.0,
        ];
    }

    protected function getStats(): array
    {
        $counts = self::trialCounts();

        return [
            Stat::make('Trials Started', (string) $counts['total'])
                ->description('Accounts that activated CineCut trial')
                ->color('primary'),

            Stat::make('Active Trials', (string) $counts['active'])
                ->description('Still inside time and usage limits')
                ->color($counts['active'] > 0 ? 'success' : 'gray'),

            Stat::make('Trials Ended', (string) $counts['ended'])
                ->description('Expired or cut/minute limit reached')
                ->color($counts['ended'] > 0 ? 'warning' : 'gray'),

            Stat::make('Converted to Pro', (string) $counts['converted'])
                ->description($counts['conversion_rate'] . '% of started trials')
                ->color($counts['converted'] > 0 ? 'success' : 'gray'),
        ];
    }
}
