<?php

namespace App\Filament\Widgets;

use App\Models\SiteVisit;
use Filament\Widgets\ChartWidget;

class VisitDeviceChart extends ChartWidget
{
    protected static ?string $heading = 'Visits by device';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $rows = SiteVisit::query()
            ->selectRaw("coalesce(nullif(device_type, ''), 'unknown') as label, count(*) as total")
            ->groupByRaw("coalesce(nullif(device_type, ''), 'unknown')")
            ->orderByDesc('total')
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
                    'backgroundColor' => ['#8b5cf6', '#f59e0b', '#14b8a6', '#64748b'],
                ],
            ],
            'labels' => $rows->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
