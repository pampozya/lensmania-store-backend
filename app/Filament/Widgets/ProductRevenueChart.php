<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class ProductRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue by product';

    protected static ?int $sort = -1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $rows = $this->paidOrders()
            ->groupBy(fn (Order $order): string => (string) ($order->product_slug ?: $order->product_name))
            ->map(fn (Collection $orders, string $product): array => [
                'product' => $product ?: 'Unknown',
                'revenue' => round($orders->sum(fn (Order $order): float => $this->amount($order)), 2),
            ])
            ->sortByDesc('revenue')
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue USD',
                    'data' => $rows->pluck('revenue')->all(),
                    'backgroundColor' => ['#f59e0b', '#8b5cf6', '#14b8a6', '#ef4444', '#64748b'],
                ],
            ],
            'labels' => $rows->pluck('product')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    private function paidOrders(): Collection
    {
        return Order::query()
            ->with('product')
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
