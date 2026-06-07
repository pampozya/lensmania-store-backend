<?php

namespace App\Filament\Widgets;

use App\Models\SiteVisit;
use Filament\Widgets\ChartWidget;

class VisitLocationChart extends ChartWidget
{
    protected static ?string $heading = 'Top visit locations';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $rows = SiteVisit::query()
            ->selectRaw("coalesce(nullif(country, ''), 'Unknown') as label, count(*) as total")
            ->groupByRaw("coalesce(nullif(country, ''), 'Unknown')")
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'datasets' => [['label' => 'Visits', 'data' => [0], 'backgroundColor' => ['#64748b']]],
                'labels' => ['No visits yet'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Visits',
                    'data' => $rows->pluck('total')->all(),
                    'backgroundColor' => ['#f59e0b', '#8b5cf6', '#14b8a6', '#ef4444', '#06b6d4', '#84cc16', '#f97316', '#64748b'],
                ],
            ],
            'labels' => $rows->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
