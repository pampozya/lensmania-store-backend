<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

final class SalesOverview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Financials';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Sales Overview';

    protected static string $view = 'filament.pages.sales-overview';

    public function getSummary(): array
    {
        $orders = $this->paidOrders();
        $today = now()->toDateString();
        $month = now()->format('Y-m');

        return [
            ['label' => 'Paid revenue', 'value' => '$' . number_format($this->sumRevenue($orders), 2), 'hint' => 'Paid or fulfilled orders'],
            ['label' => 'Orders', 'value' => (string) $orders->count(), 'hint' => 'Paid or fulfilled'],
            ['label' => 'Today', 'value' => (string) $orders->filter(fn (Order $order): bool => optional($order->paid_at ?? $order->purchased_at ?? $order->created_at)->toDateString() === $today)->count(), 'hint' => now()->format('M j, Y')],
            ['label' => 'This month', 'value' => (string) $orders->filter(fn (Order $order): bool => optional($order->paid_at ?? $order->purchased_at ?? $order->created_at)->format('Y-m') === $month)->count(), 'hint' => now()->format('F Y')],
        ];
    }

    public function getProductRows(): array
    {
        return $this->paidOrders()
            ->groupBy(fn (Order $order): string => (string) ($order->product_slug ?: $order->product_name))
            ->map(function (Collection $orders, string $product): array {
                return [
                    'product' => $product ?: 'Unknown',
                    'orders' => $orders->count(),
                    'revenue' => $this->sumRevenue($orders),
                    'average' => $orders->count() > 0 ? $this->sumRevenue($orders) / $orders->count() : 0,
                ];
            })
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }

    public function getRecentOrders(): array
    {
        return $this->paidOrders()
            ->sortByDesc(fn (Order $order) => optional($order->paid_at ?? $order->purchased_at ?? $order->created_at)->timestamp)
            ->take(10)
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'customer' => $order->user?->email ?? 'Unknown',
                'product' => $order->product_slug ?: $order->product_name,
                'amount' => $this->amount($order),
                'status' => $order->api_status ?: $order->status,
                'date' => optional($order->paid_at ?? $order->purchased_at ?? $order->created_at)->format('M j, Y H:i') ?? '-',
            ])
            ->values()
            ->all();
    }

    private function paidOrders(): Collection
    {
        return Order::query()
            ->with(['user', 'product'])
            ->where(function ($query): void {
                $query
                    ->where('status', 'paid')
                    ->orWhereIn('api_status', ['paid', 'fulfilled']);
            })
            ->get();
    }

    private function sumRevenue(Collection $orders): float
    {
        return (float) $orders->sum(fn (Order $order): float => $this->amount($order));
    }

    private function amount(Order $order): float
    {
        if ($order->amount_usd !== null) {
            return (float) $order->amount_usd;
        }

        return $order->amount_cents !== null ? ((int) $order->amount_cents) / 100 : 0;
    }
}
