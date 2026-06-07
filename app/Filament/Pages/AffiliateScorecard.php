<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\SiteEvent;
use App\Models\SiteVisit;
use Filament\Pages\Page;

final class AffiliateScorecard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Affiliate Scorecard';
    protected static ?string $navigationGroup = 'Growth';
    protected static ?string $title = 'Affiliate Scorecard';
    protected static string $view = 'filament.pages.analytics-report';

    public function getSections(): array
    {
        $affiliates = collect(['youssef', 'noor'])
            ->merge(SiteEvent::query()->whereNotNull('affiliate')->distinct()->pluck('affiliate'))
            ->merge(SiteVisit::query()->whereNotNull('affiliate')->distinct()->pluck('affiliate'))
            ->filter()
            ->unique()
            ->values();

        $rows = $affiliates->map(function (string $affiliate): array {
            $visits = SiteVisit::query()->where('affiliate', $affiliate)->count();
            $clicks = SiteEvent::query()->where('affiliate', $affiliate)->where('name', 'buy_clicked')->count();
            $orders = Order::query()->whereHas('product')->where(function ($query): void {
                $query->where('status', 'paid')->orWhereIn('api_status', ['paid', 'fulfilled']);
            })->where(function ($query) use ($affiliate): void {
                $query->where('promo_code', 'like', '%' . strtoupper($affiliate) . '%');
            })->get();

            return [$affiliate, (string) $visits, (string) $clicks, (string) $orders->count(), '$' . number_format($orders->sum(fn (Order $order): float => $this->amount($order)), 2), $clicks > 0 ? number_format(($orders->count() / $clicks) * 100, 1) . '%' : '0.0%'];
        })->all();

        return [[
            'title' => 'Affiliate performance',
            'description' => 'Compares tracked traffic/clicks with paid sales attributed by promo code naming.',
            'columns' => ['Affiliate', 'Visits', 'Buy clicks', 'Sales', 'Revenue', 'Click conversion'],
            'rows' => $rows,
        ]];
    }

    private function amount(Order $order): float
    {
        return $order->amount_usd !== null ? (float) $order->amount_usd : (((int) $order->amount_cents) / 100);
    }
}
