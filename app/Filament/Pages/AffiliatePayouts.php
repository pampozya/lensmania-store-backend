<?php

namespace App\Filament\Pages;

use App\Models\Affiliate;
use Filament\Pages\Page;

final class AffiliatePayouts extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Affiliate Payouts';

    protected static ?string $navigationGroup = 'Growth';

    protected static ?string $title = 'Affiliate Payouts';

    protected static string $view = 'filament.pages.affiliate-payouts';

    public function getRows(): array
    {
        return Affiliate::query()
            ->with('user')
            ->latest('created_at')
            ->get()
            ->map(function (Affiliate $affiliate): array {
                $purchaseEvents = $affiliate->events()->where('type', 'purchase');
                $revenueCents = (int) $purchaseEvents->sum('revenue_cents');
                $minPayoutCents = (int) $affiliate->min_payout_cents;

                return [
                    'code' => $affiliate->code,
                    'label' => $affiliate->label,
                    'account' => $affiliate->user?->email ?? '-',
                    'status' => $affiliate->status,
                    'visits' => $affiliate->visits()->count(),
                    'clicks' => $affiliate->events()->where('type', 'checkout_click')->count(),
                    'purchases' => $purchaseEvents->count(),
                    'revenue' => $revenueCents / 100,
                    'threshold' => $minPayoutCents / 100,
                    'ready' => $revenueCents >= $minPayoutCents && $revenueCents > 0,
                ];
            })
            ->all();
    }
}
