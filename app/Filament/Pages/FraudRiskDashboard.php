<?php

namespace App\Filament\Pages;

use App\Models\DownloadToken;
use App\Models\License;
use App\Models\Order;
use App\Models\SiteEvent;
use App\Models\SiteVisit;
use Filament\Pages\Page;

final class FraudRiskDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationLabel = 'Fraud / Risk Dashboard';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $title = 'Fraud / Risk Dashboard';
    protected static string $view = 'filament.pages.analytics-report';

    public function getCards(): array
    {
        $multiPromoVisitors = SiteEvent::query()->whereNotNull('visitor_hash')->whereNotNull('promo_code')->get()->groupBy('visitor_hash')->filter(fn ($group): bool => $group->pluck('promo_code')->unique()->count() >= 3)->count();
        $manyVisitsSameIp = SiteVisit::query()->whereNotNull('ip_hash')->get()->groupBy('ip_hash')->filter(fn ($group): bool => $group->count() >= 30)->count();
        $expiredTokens = DownloadToken::query()->whereNull('used_at')->where('expires_at', '<', now())->count();
        $atCap = License::query()->withCount(['devices' => fn ($query) => $query->where('status', 'active')])->get()->filter(fn (License $license): bool => $license->devices_count >= 2)->count();

        return [
            ['label' => 'Multi-promo visitors', 'value' => (string) $multiPromoVisitors, 'hint' => '3+ promo codes'],
            ['label' => 'High-volume IP hashes', 'value' => (string) $manyVisitsSameIp, 'hint' => '30+ visits'],
            ['label' => 'Expired download tokens', 'value' => (string) $expiredTokens, 'hint' => 'Unused expired links'],
            ['label' => 'Licenses at device cap', 'value' => (string) $atCap, 'hint' => '2 active devices'],
        ];
    }

    public function getSections(): array
    {
        $paymentIssues = Order::query()
            ->where(function ($query): void {
                $query->where('status', 'paid')->orWhereIn('api_status', ['paid', 'fulfilled']);
            })
            ->whereNull('paypal_capture_id')
            ->whereNull('paypal_payment_id')
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (Order $order): array => ['#' . $order->id, $order->user?->email ?? '-', $order->product_slug ?: '-', $order->status . '/' . $order->api_status, 'Missing PayPal capture/payment id'])
            ->all();

        return [[
            'title' => 'Payment rows needing review',
            'description' => 'Paid-looking orders without a PayPal capture/payment id.',
            'columns' => ['Order', 'Customer', 'Product', 'Status', 'Risk'],
            'rows' => $paymentIssues,
        ]];
    }
}
