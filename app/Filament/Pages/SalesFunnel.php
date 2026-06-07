<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\SiteEvent;
use App\Models\SiteVisit;
use Filament\Pages\Page;

final class SalesFunnel extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Sales Funnel';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 15;
    protected static ?string $title = 'Sales Funnel';
    protected static string $view = 'filament.pages.analytics-report';

    public function getCards(): array
    {
        $visits = SiteVisit::query()->count();
        $productViews = SiteVisit::query()->where(function ($query): void {
            $query->where('path', 'like', '%hushcut%')
                ->orWhere('path', 'like', '%babelcut%')
                ->orWhere('path', 'like', '%buy.html%');
        })->count();
        $promoApplies = SiteEvent::query()->where('name', 'promo_applied')->count();
        $buyClicks = SiteEvent::query()->where('name', 'buy_clicked')->count();
        $paidOrders = $this->paidOrders()->count();

        return [
            ['label' => 'Visits', 'value' => (string) $visits, 'hint' => 'Tracked page visits'],
            ['label' => 'Product views', 'value' => (string) $productViews, 'hint' => $this->rate($productViews, $visits) . ' of visits'],
            ['label' => 'Buy clicks', 'value' => (string) $buyClicks, 'hint' => $this->rate($buyClicks, max($productViews, 1)) . ' of product views'],
            ['label' => 'Paid orders', 'value' => (string) $paidOrders, 'hint' => $this->rate($paidOrders, max($buyClicks, 1)) . ' of buy clicks'],
        ];
    }

    public function getSections(): array
    {
        $rows = collect([
            ['Visit', SiteVisit::query()->count()],
            ['Product/detail view', SiteVisit::query()->where(function ($query): void {
                $query->where('path', 'like', '%hushcut%')
                    ->orWhere('path', 'like', '%babelcut%')
                    ->orWhere('path', 'like', '%buy.html%');
            })->count()],
            ['Promo applied', SiteEvent::query()->where('name', 'promo_applied')->count()],
            ['Buy clicked', SiteEvent::query()->where('name', 'buy_clicked')->count()],
            ['Checkout started', SiteEvent::query()->whereIn('name', ['InitiateCheckout', 'begin_checkout'])->count()],
            ['Paid order', $this->paidOrders()->count()],
            ['Fulfilled order', Order::query()->where('api_status', 'fulfilled')->count()],
        ])->map(fn (array $row): array => [$row[0], (string) $row[1]])->all();

        return [[
            'title' => 'Funnel steps',
            'description' => 'Counts come from site tracking events and paid/fulfilled orders.',
            'columns' => ['Step', 'Count'],
            'rows' => $rows,
        ]];
    }

    private function paidOrders()
    {
        return Order::query()->where(function ($query): void {
            $query->where('status', 'paid')->orWhereIn('api_status', ['paid', 'fulfilled']);
        });
    }

    private function rate(int $value, int $base): string
    {
        return $base > 0 ? number_format(($value / $base) * 100, 1) . '%' : '0.0%';
    }
}
