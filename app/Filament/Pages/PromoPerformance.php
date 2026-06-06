<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Pages\Page;

class PromoPerformance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Promo Performance';

    protected static ?string $title = 'Promo Performance';

    protected static string $view = 'filament.pages.promo-performance';

    /**
     * @return array<int, array{code: string, label: string, sales: int, revenue: float}>
     */
    public function getRows(): array
    {
        $knownCodes = [
            'YOUSSEF10'  => 'Youssef 10%',
            'NOOR10'     => 'Noor 10%',
            'YOUSSEFVIP' => 'Youssef VIP',
            'NOORVIP'    => 'Noor VIP',
        ];

        $rows = [];

        foreach ($knownCodes as $code => $label) {
            $rows[] = $this->statsFor($code, $label);
        }

        // "(none)" bucket — fulfilled orders with no promo_code
        $rows[] = $this->statsForNone();

        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return $rows;
    }

    private function statsFor(string $code, string $label): array
    {
        $orders = Order::query()
            ->where('api_status', 'fulfilled')
            ->where('promo_code', $code)
            ->get(['amount_usd', 'amount_cents']);

        return [
            'code'    => $code,
            'label'   => $label,
            'sales'   => $orders->count(),
            'revenue' => $this->sumRevenue($orders),
        ];
    }

    private function statsForNone(): array
    {
        $orders = Order::query()
            ->where('api_status', 'fulfilled')
            ->where(function ($q) {
                $q->whereNull('promo_code')->orWhere('promo_code', '');
            })
            ->get(['amount_usd', 'amount_cents']);

        return [
            'code'    => '(none)',
            'label'   => 'No promo code',
            'sales'   => $orders->count(),
            'revenue' => $this->sumRevenue($orders),
        ];
    }

    private function sumRevenue($orders): float
    {
        return (float) $orders->sum(function ($o) {
            if ($o->amount_usd !== null) {
                return (float) $o->amount_usd;
            }
            return $o->amount_cents !== null ? $o->amount_cents / 100 : 0;
        });
    }
}
