<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class RevenueTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue trend';

    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $labels = [];
        $revenue = [];
        $orders = [];
        $paidOrders = $this->paidOrders()
            ->filter(fn (Order $order): bool => optional($this->orderDate($order))->greaterThanOrEqualTo($start));

        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo(now()->startOfMonth()); $cursor->addMonth()) {
            $key = $cursor->format('Y-m');
            $monthOrders = $paidOrders->filter(fn (Order $order): bool => optional($this->orderDate($order))->format('Y-m') === $key);

            $labels[] = $cursor->format('M Y');
            $revenue[] = round($monthOrders->sum(fn (Order $order): float => $this->amount($order)), 2);
            $orders[] = $monthOrders->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue USD',
                    'data' => $revenue,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.16)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Orders',
                    'data' => $orders,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.12)',
                    'fill' => false,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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

    private function orderDate(Order $order): mixed
    {
        return $order->paid_at ?? $order->purchased_at ?? $order->created_at;
    }

    private function amount(Order $order): float
    {
        if ($order->amount_usd !== null) {
            return (float) $order->amount_usd;
        }

        return $order->amount_cents !== null ? ((int) $order->amount_cents) / 100 : 0;
    }
}
