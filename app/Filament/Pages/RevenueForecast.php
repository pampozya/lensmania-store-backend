<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;

final class RevenueForecast extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Revenue Forecast';
    protected static ?string $navigationGroup = 'Revenue';
    protected static ?string $title = 'Revenue Forecast';
    protected static string $view = 'filament.pages.analytics-report';

    public function getCards(): array
    {
        $monthRevenue = $this->monthRevenue();
        $day = max((int) now()->format('j'), 1);
        $daysInMonth = now()->daysInMonth;
        $forecast = ($monthRevenue / $day) * $daysInMonth;

        return [
            ['label' => 'This month', 'value' => '$' . number_format($monthRevenue, 2), 'hint' => now()->format('F Y')],
            ['label' => 'Forecast month-end', 'value' => '$' . number_format($forecast, 2), 'hint' => 'Linear run-rate'],
            ['label' => 'Avg daily revenue', 'value' => '$' . number_format($monthRevenue / $day, 2), 'hint' => 'Month to date'],
            ['label' => 'Days remaining', 'value' => (string) max($daysInMonth - $day, 0), 'hint' => 'In current month'],
        ];
    }

    public function getSections(): array
    {
        $rows = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $orders = $this->paidOrders()->get()->filter(fn (Order $order): bool => optional($order->paid_at ?? $order->purchased_at ?? $order->created_at)->format('Y-m') === $month->format('Y-m'));
            $rows[] = [$month->format('M Y'), (string) $orders->count(), '$' . number_format($orders->sum(fn (Order $order): float => $this->amount($order)), 2)];
        }

        return [[
            'title' => 'Monthly revenue history',
            'description' => 'Used for the run-rate estimate.',
            'columns' => ['Month', 'Orders', 'Revenue'],
            'rows' => $rows,
        ]];
    }

    private function monthRevenue(): float
    {
        return (float) $this->paidOrders()->get()->filter(fn (Order $order): bool => optional($order->paid_at ?? $order->purchased_at ?? $order->created_at)->format('Y-m') === now()->format('Y-m'))->sum(fn (Order $order): float => $this->amount($order));
    }

    private function paidOrders()
    {
        return Order::query()->where(function ($query): void {
            $query->where('status', 'paid')->orWhereIn('api_status', ['paid', 'fulfilled']);
        });
    }

    private function amount(Order $order): float
    {
        return $order->amount_usd !== null ? (float) $order->amount_usd : (((int) $order->amount_cents) / 100);
    }
}
