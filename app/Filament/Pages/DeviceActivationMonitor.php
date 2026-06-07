<?php

namespace App\Filament\Pages;

use App\Models\License;
use App\Models\LicenseDevice;
use Filament\Pages\Page;

final class DeviceActivationMonitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Device Activation Monitor';
    protected static ?string $navigationGroup = 'Customers';
    protected static ?string $title = 'Device Activation Monitor';
    protected static string $view = 'filament.pages.analytics-report';

    public function getCards(): array
    {
        $activeDevices = LicenseDevice::query()->where('status', 'active')->count();
        $nearCap = License::query()->withCount(['devices' => fn ($query) => $query->where('status', 'active')])->get()->filter(fn (License $license): bool => $license->devices_count >= 2)->count();

        return [
            ['label' => 'Active devices', 'value' => (string) $activeDevices, 'hint' => 'Activated machines'],
            ['label' => 'Licenses at cap', 'value' => (string) $nearCap, 'hint' => '2 active devices'],
            ['label' => 'Locked/revoked devices', 'value' => (string) LicenseDevice::query()->whereNot('status', 'active')->count(), 'hint' => 'Not active'],
            ['label' => 'Recent validations', 'value' => (string) LicenseDevice::query()->where('last_validated_at', '>=', now()->subDay())->count(), 'hint' => 'Last 24 hours'],
        ];
    }

    public function getSections(): array
    {
        $rows = LicenseDevice::query()
            ->with(['license.user', 'license.product'])
            ->latest('last_validated_at')
            ->limit(50)
            ->get()
            ->map(fn (LicenseDevice $device): array => [
                $device->license?->license_key ?? '-',
                $device->license?->user?->email ?? '-',
                $device->license?->product?->name ?? '-',
                $device->device_label ?: $device->device_id,
                $device->platform ?: '-',
                $device->app_version ?: '-',
                $device->status,
                optional($device->last_validated_at)->format('M j, Y H:i') ?: '-',
            ])
            ->all();

        return [[
            'title' => 'Recent device activity',
            'description' => 'Shows activated devices and the last validation seen by the licensing system.',
            'columns' => ['License', 'Customer', 'Product', 'Device', 'Platform', 'Version', 'Status', 'Last validated'],
            'rows' => $rows,
        ]];
    }
}
