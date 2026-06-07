<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AffiliatePayouts;
use App\Filament\Pages\AffiliateScorecard;
use App\Filament\Pages\AbandonedCheckout;
use App\Filament\Pages\CohortAnalytics;
use App\Filament\Pages\CustomerTimeline;
use App\Filament\Pages\DeviceActivationMonitor;
use App\Filament\Pages\DownloadHealth;
use App\Filament\Pages\EmailDeliveryMonitor;
use App\Filament\Pages\FraudRiskDashboard;
use App\Filament\Pages\GeoHeatmap;
use App\Filament\Pages\PayPalReconciliation;
use App\Filament\Pages\PayPalCsvImporter;
use App\Filament\Pages\ProductVersionAnalytics;
use App\Filament\Pages\PromoLeaderboard;
use App\Filament\Pages\RevenueForecast;
use App\Filament\Pages\SalesOverview;
use App\Filament\Pages\SalesFunnel;
use App\Filament\Pages\SupportTools;
use App\Filament\Pages\SystemHealth;
use App\Filament\Pages\VisitAnalytics;
use App\Filament\Resources\Affiliates\AffiliatesResource;
use App\Filament\Resources\AuditLogs\AuditLogsResource;
use App\Filament\Resources\DownloadTokens\DownloadTokensResource;
use App\Filament\Resources\EmailDeliveryLogs\EmailDeliveryLogsResource;
use App\Filament\Resources\LicenseDevices\LicenseDevicesResource;
use App\Filament\Resources\Orders\OrdersResource;
use App\Filament\Resources\Licenses\LicensesResource;
use App\Filament\Resources\SupportNotes\SupportNotesResource;
use App\Filament\Resources\Users\UsersResource;
use App\Filament\Resources\StorefrontPromos\StorefrontPromosResource;
use App\Filament\Widgets\OperationalRiskOverview;
use App\Filament\Widgets\ProductRevenueChart;
use App\Filament\Widgets\PromoAnalyticsOverview;
use App\Filament\Widgets\RevenueTrendChart;
use App\Filament\Widgets\StoreStatsOverview;
use App\Filament\Widgets\VisitAnalyticsOverview;
use App\Filament\Widgets\VisitDeviceChart;
use App\Filament\Widgets\VisitLocationChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                OrdersResource::class,
                UsersResource::class,
                LicensesResource::class,
                LicenseDevicesResource::class,
                AffiliatesResource::class,
                DownloadTokensResource::class,
                SupportNotesResource::class,
                AuditLogsResource::class,
                EmailDeliveryLogsResource::class,
                StorefrontPromosResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                SalesOverview::class,
                RevenueForecast::class,
                PayPalReconciliation::class,
                PayPalCsvImporter::class,
                AffiliatePayouts::class,
                AffiliateScorecard::class,
                VisitAnalytics::class,
                SalesFunnel::class,
                PromoLeaderboard::class,
                AbandonedCheckout::class,
                GeoHeatmap::class,
                ProductVersionAnalytics::class,
                CohortAnalytics::class,
                CustomerTimeline::class,
                DeviceActivationMonitor::class,
                DownloadHealth::class,
                FraudRiskDashboard::class,
                EmailDeliveryMonitor::class,
                SupportTools::class,
                SystemHealth::class,
                \App\Filament\Pages\PromoPerformance::class,
            ])
            ->widgets([
                StoreStatsOverview::class,
                VisitAnalyticsOverview::class,
                RevenueTrendChart::class,
                ProductRevenueChart::class,
                PromoAnalyticsOverview::class,
                OperationalRiskOverview::class,
                VisitLocationChart::class,
                VisitDeviceChart::class,
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
