<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\SiteEvent;
use Filament\Pages\Page;

final class AbandonedCheckout extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Abandoned Checkout';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?string $title = 'Failed / Abandoned Checkout';
    protected static string $view = 'filament.pages.analytics-report';

    public function getCards(): array
    {
        $clicks = SiteEvent::query()->where('name', 'buy_clicked')->count();
        $orders = $this->paidOrders()->count();
        $gap = max($clicks - $orders, 0);

        return [
            ['label' => 'Buy clicks', 'value' => (string) $clicks, 'hint' => 'Tracked button clicks'],
            ['label' => 'Paid orders', 'value' => (string) $orders, 'hint' => 'Paid/fulfilled orders'],
            ['label' => 'Potential abandons', 'value' => (string) $gap, 'hint' => 'Clicks minus paid orders'],
            ['label' => 'Click conversion', 'value' => $clicks > 0 ? number_format(($orders / $clicks) * 100, 1) . '%' : '0.0%', 'hint' => 'Paid orders / buy clicks'],
        ];
    }

    public function getSections(): array
    {
        $products = SiteEvent::query()->where('name', 'buy_clicked')->whereNotNull('product_slug')->distinct()->pluck('product_slug');
        $rows = $products->map(function (string $product): array {
            $clicks = SiteEvent::query()->where('name', 'buy_clicked')->where('product_slug', $product)->count();
            $paid = $this->paidOrders()->where('product_slug', $product)->count();

            return [$product, (string) $clicks, (string) $paid, (string) max($clicks - $paid, 0), $clicks > 0 ? number_format(($paid / $clicks) * 100, 1) . '%' : '0.0%'];
        })->all();

        return [[
            'title' => 'Checkout gap by product',
            'description' => 'Approximate abandonment because static PayPal links do not currently send a guaranteed checkout-start callback.',
            'columns' => ['Product', 'Buy clicks', 'Paid orders', 'Gap', 'Conversion'],
            'rows' => $rows,
        ]];
    }

    private function paidOrders()
    {
        return Order::query()->where(function ($query): void {
            $query->where('status', 'paid')->orWhereIn('api_status', ['paid', 'fulfilled']);
        });
    }
}
