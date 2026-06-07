<?php

namespace App\Filament\Resources\Licenses;

use App\Filament\Resources\Licenses\Pages\ListLicenses;
use App\Models\License;
use App\Models\LicenseDevice;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class LicensesResource extends Resource
{
    protected static ?string $model = License::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Licenses';

    protected static ?string $navigationGroup = 'Customers';

    protected static ?string $pluralModelLabel = 'Licenses';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_key')
                    ->label('License key')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('user.email')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'revoked' => 'danger',
                        'inactive' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('devices_count')
                    ->label('Devices')
                    ->counts('devices')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'revoked' => 'Revoked',
                    ]),
            ])
            ->actions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (License $record): bool => $record->status === 'active')
                    ->action(function (License $record): void {
                        $record->forceFill(['status' => 'revoked'])->save();
                        Notification::make()->title('License revoked')->danger()->send();
                    }),

                Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (License $record): bool => $record->status === 'revoked')
                    ->action(function (License $record): void {
                        $record->forceFill(['status' => 'active'])->save();
                        Notification::make()->title('License reactivated')->success()->send();
                    }),

                Action::make('reset_devices')
                    ->label('Reset Devices')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This clears all activated devices so the customer can re-activate on a new machine.')
                    ->action(function (License $record): void {
                        $count = LicenseDevice::query()->where('license_id', $record->id)->count();
                        LicenseDevice::query()->where('license_id', $record->id)->delete();
                        Notification::make()
                            ->title('Devices reset')
                            ->body($count . ' device(s) cleared.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenses::route('/'),
        ];
    }
}
