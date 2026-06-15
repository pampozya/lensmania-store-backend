<?php

namespace App\Filament\Pages;

use App\Models\SiteVisit;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

final class VisitAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Traffic Analytics';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Visit Analytics';

    protected static string $view = 'filament.pages.visit-analytics';

    /** @var array<int, array<string, mixed>> */
    public array $locations = [];

    /** @var array<int, array<string, mixed>> */
    public array $devices = [];

    /** @var array<int, array<string, mixed>> */
    public array $recentVisits = [];

    public ?string $lastUpdated = null;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->locations = $this->fetchLocations();
        $this->devices = $this->fetchDevices();
        $this->recentVisits = $this->fetchRecentVisits();
        $this->lastUpdated = now()->format('M j, Y H:i:s');
    }

    public function refresh(): void
    {
        $this->loadData();

        Notification::make()
            ->title('Analytics refreshed')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->refresh()),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    protected function fetchLocations(): array
    {
        return SiteVisit::query()
            ->selectRaw("coalesce(nullif(country, ''), 'Unknown') as country, coalesce(nullif(city, ''), 'Unknown') as city, count(*) as visits, count(distinct visitor_hash) as visitors")
            ->groupByRaw("coalesce(nullif(country, ''), 'Unknown'), coalesce(nullif(city, ''), 'Unknown')")
            ->orderByDesc('visits')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => [
                'country' => $row->country,
                'city' => $row->city,
                'visits' => (int) $row->visits,
                'visitors' => (int) $row->visitors,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function fetchDevices(): array
    {
        return SiteVisit::query()
            ->selectRaw("coalesce(nullif(device_type, ''), 'unknown') as device, coalesce(nullif(os, ''), 'unknown') as os, coalesce(nullif(browser, ''), 'unknown') as browser, count(*) as visits")
            ->groupByRaw("coalesce(nullif(device_type, ''), 'unknown'), coalesce(nullif(os, ''), 'unknown'), coalesce(nullif(browser, ''), 'unknown')")
            ->orderByDesc('visits')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => [
                'device' => $row->device,
                'os' => $row->os,
                'browser' => $row->browser,
                'visits' => (int) $row->visits,
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function fetchRecentVisits(): array
    {
        return SiteVisit::query()
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (SiteVisit $visit): array => [
                'when' => optional($visit->created_at)->format('M j, Y H:i') ?? '-',
                'path' => $visit->path ?: '-',
                'location' => trim(implode(', ', array_filter([$visit->city, $visit->country]))) ?: 'Unknown',
                'device' => trim(implode(' / ', array_filter([$visit->device_type, $visit->os, $visit->browser]))) ?: 'Unknown',
                'promo' => $visit->promo_code ?: '-',
                'referrer' => $visit->referrer ?: '-',
            ])
            ->all();
    }
}
