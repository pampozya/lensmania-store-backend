<?php

namespace App\Filament\Pages;

use App\Models\SiteVisit;
use Filament\Pages\Page;

final class GeoHeatmap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Geo Heatmap';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?string $title = 'Geo Heatmap';
    protected static string $view = 'filament.pages.analytics-report';

    public function getSections(): array
    {
        $locations = SiteVisit::query()->latest()->get()
            ->groupBy(fn (SiteVisit $visit): string => ($visit->country ?: 'Unknown') . '|' . ($visit->city ?: 'Unknown'))
            ->map(fn ($group, string $key): array => [
                ...explode('|', $key),
                (string) $group->count(),
                (string) $group->pluck('visitor_hash')->filter()->unique()->count(),
                $group->sortByDesc('created_at')->first()?->created_at?->format('M j, Y H:i') ?? '-',
            ])
            ->sortByDesc(fn (array $row): int => (int) $row[2])
            ->take(40)
            ->values()
            ->all();

        return [[
            'title' => 'Country / city heat table',
            'description' => 'Table version of a heatmap. Accuracy depends on geo headers or browser locale fallback.',
            'columns' => ['Country', 'City', 'Visits', 'Visitors', 'Last seen'],
            'rows' => $locations,
        ]];
    }
}
