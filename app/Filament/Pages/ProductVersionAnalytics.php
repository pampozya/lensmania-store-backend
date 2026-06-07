<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;

final class ProductVersionAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Product Version Analytics';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?string $title = 'Product / Version Analytics';
    protected static string $view = 'filament.pages.analytics-report';

    public function getSections(): array
    {
        $orders = Order::query()->whereNotNull('selection_metadata')->get();
        $rows = $orders->flatMap(function (Order $order): array {
            $meta = $order->selection_metadata ?: [];
            if (($order->product_slug ?? '') === 'bundle' && isset($meta['bundle']) && is_array($meta['bundle'])) {
                return collect($meta['bundle'])->map(fn ($selection, $product): array => [
                    (string) $product,
                    (string) ($selection['platform'] ?? '-'),
                    (string) ($selection['app'] ?? $selection['application'] ?? '-'),
                    (string) ($selection['version'] ?? '-'),
                ])->all();
            }

            return [[
                (string) ($order->product_slug ?: $order->product_name),
                (string) ($meta['platform'] ?? '-'),
                (string) ($meta['app'] ?? $meta['application'] ?? '-'),
                (string) ($meta['version'] ?? '-'),
            ]];
        })
            ->groupBy(fn (array $row): string => implode('|', $row))
            ->map(fn ($group, string $key): array => [...explode('|', $key), (string) $group->count()])
            ->sortByDesc(fn (array $row): int => (int) $row[4])
            ->values()
            ->all();

        return [[
            'title' => 'Selected versions',
            'description' => 'Shows what customers selected before checkout, including bundle choices.',
            'columns' => ['Product', 'Platform', 'App', 'Version', 'Orders'],
            'rows' => $rows,
        ]];
    }
}
