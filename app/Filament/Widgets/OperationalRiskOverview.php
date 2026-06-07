<?php

namespace App\Filament\Widgets;

use App\Models\DownloadToken;
use App\Models\License;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationalRiskOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $pendingFulfillment = Order::query()->where('api_status', 'paid')->count();
        $missingPaypalIds = Order::query()
            ->where(function ($query): void {
                $query->where('status', 'paid')
                    ->orWhereIn('api_status', ['paid', 'fulfilled']);
            })
            ->whereNull('paypal_capture_id')
            ->whereNull('paypal_payment_id')
            ->count();
        $expiredDownloads = DownloadToken::query()
            ->whereNull('used_at')
            ->where('expires_at', '<', now())
            ->count();
        $revokedLicenses = License::query()->where('status', 'revoked')->count();

        return [
            Stat::make('Pending Fulfillment', (string) $pendingFulfillment)
                ->description($pendingFulfillment > 0 ? 'Needs manual review' : 'All clear')
                ->color($pendingFulfillment > 0 ? 'warning' : 'success'),

            Stat::make('Missing PayPal IDs', (string) $missingPaypalIds)
                ->description('Paid rows without capture/payment id')
                ->color($missingPaypalIds > 0 ? 'danger' : 'success'),

            Stat::make('Expired Download Links', (string) $expiredDownloads)
                ->description('Unused and expired tokens')
                ->color($expiredDownloads > 0 ? 'warning' : 'gray'),

            Stat::make('Revoked Licenses', (string) $revokedLicenses)
                ->description('Customer access disabled')
                ->color($revokedLicenses > 0 ? 'danger' : 'gray'),
        ];
    }
}
