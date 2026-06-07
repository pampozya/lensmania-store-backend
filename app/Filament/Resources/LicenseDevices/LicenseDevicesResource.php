<?php

namespace App\Filament\Resources\LicenseDevices;

use App\Filament\Resources\LicenseDevices\Pages\ListLicenseDevices;
use App\Models\LicenseDevice;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class LicenseDevicesResource extends Resource
{
    protected static ?string $model = LicenseDevice::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'license-devices';

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'License Devices';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $pluralModelLabel = 'License Devices';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license.license_key')->label('License')->searchable()->copyable(),
                TextColumn::make('license.user.email')->label('Customer')->searchable(),
                TextColumn::make('license.product.name')->label('Product'),
                TextColumn::make('device_id')->label('Device ID')->limit(24)->copyable()->searchable(),
                TextColumn::make('device_label')->label('Label')->searchable(),
                TextColumn::make('platform')->badge(),
                TextColumn::make('app_version')->label('App version'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'active' ? 'success' : 'warning'),
                TextColumn::make('first_activated_at')->label('First activated')->dateTime('M j, Y H:i')->sortable(),
                TextColumn::make('last_validated_at')->label('Last validated')->dateTime('M j, Y H:i')->sortable(),
            ])
            ->defaultSort('last_validated_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'deactivated' => 'Deactivated',
                    'locked' => 'Locked',
                ]),
                SelectFilter::make('platform')->options([
                    'macos' => 'macOS',
                    'windows' => 'Windows',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenseDevices::route('/'),
        ];
    }
}
