<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AffiliatePayouts;
use App\Filament\Pages\DownloadHealth;
use App\Filament\Pages\FraudRiskDashboard;
use App\Filament\Pages\LicenseIssuer;
use App\Filament\Pages\PayPalReconciliation;
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
use App\Filament\Resources\Trials\TrialsResource;
use App\Filament\Resources\Users\UsersResource;
use App\Filament\Resources\StorefrontPromos\StorefrontPromosResource;
use App\Filament\Resources\SiteVisits\SiteVisitsResource;
use App\Filament\Widgets\OperationalRiskOverview;
use App\Filament\Widgets\ProductRevenueChart;
use App\Filament\Widgets\PromoAnalyticsOverview;
use App\Filament\Widgets\RevenueTrendChart;
use App\Filament\Widgets\StoreStatsOverview;
use App\Filament\Widgets\TrialAnalyticsOverview;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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
            ->font('Space Grotesk')
            ->defaultThemeMode(ThemeMode::Dark)
            ->colors([
                'primary' => [
                    50 => '#fff8db',
                    100 => '#ffeeb0',
                    200 => '#ffe080',
                    300 => '#ffd04f',
                    400 => '#e9bd36',
                    500 => '#d4af37',
                    600 => '#ae8525',
                    700 => '#87631c',
                    800 => '#624716',
                    900 => '#3a2a0d',
                    950 => '#1d1405',
                ],
                'danger' => Color::Red,
                'success' => Color::Green,
                'gray' => [
                    50 => '#f6f6f7',
                    100 => '#e7e7ea',
                    200 => '#cfcfd5',
                    300 => '#a8a8b3',
                    400 => '#777784',
                    500 => '#5d5d68',
                    600 => '#46464f',
                    700 => '#303039',
                    800 => '#191920',
                    900 => '#0b0b10',
                    950 => '#050505',
                ],
            ])
            ->navigationGroups([
                NavigationGroup::make('Dashboard'),
                NavigationGroup::make('Operations'),
                NavigationGroup::make('Growth & Campaigns'),
                NavigationGroup::make('Analytics'),
                NavigationGroup::make('System & Security'),
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
                SiteVisitsResource::class,
                TrialsResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                SalesOverview::class,
                PayPalReconciliation::class,
                LicenseIssuer::class,
                AffiliatePayouts::class,
                VisitAnalytics::class,
                SalesFunnel::class,
                DownloadHealth::class,
                FraudRiskDashboard::class,
                SupportTools::class,
                SystemHealth::class,
                \App\Filament\Pages\PromoPerformance::class,
            ])
            ->widgets([
                StoreStatsOverview::class,
                TrialAnalyticsOverview::class,
                RevenueTrendChart::class,
                ProductRevenueChart::class,
                PromoAnalyticsOverview::class,
                OperationalRiskOverview::class,
                Widgets\AccountWidget::class,
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
