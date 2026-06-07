<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\License;
use App\Models\LicenseDevice;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LicensesRelationManager extends RelationManager
{
    protected static string $relationship = 'licenses';

    protected static ?string $title = 'Licenses & Devices';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('license_key')
            ->columns([
                Tables\Columns\TextColumn::make('license_key')
                    ->label('License key')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->placeholder('Unknown'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'revoked' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('devices_count')
                    ->label('Devices')
                    ->counts('devices')
                    ->sortable(),
                Tables\Columns\TextColumn::make('active_devices')
                    ->label('Active devices')
                    ->state(function (License $record): string {
                        $devices = $record->devices
                            ->where('status', 'active')
                            ->map(fn (LicenseDevice $device): string => trim(implode(' / ', array_filter([
                                $device->device_label ?: substr((string) $device->device_id, 0, 12),
                                $device->platform,
                                $device->app_version,
                            ]))))
                            ->filter()
                            ->values();

                        return $devices->isEmpty() ? 'No active devices' : $devices->join(', ');
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('reset_devices')
                    ->label('Reset devices')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This clears activated devices so the customer can activate on a new machine.')
                    ->action(function (License $record): void {
                        $count = LicenseDevice::query()->where('license_id', $record->id)->count();
                        LicenseDevice::query()->where('license_id', $record->id)->delete();

                        Notification::make()
                            ->title('Devices reset')
                            ->body($count . ' device(s) cleared for this license.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
