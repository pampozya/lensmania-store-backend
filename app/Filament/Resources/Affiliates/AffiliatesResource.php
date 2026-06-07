<?php

namespace App\Filament\Resources\Affiliates;

use App\Filament\Resources\Affiliates\Pages\ListAffiliates;
use App\Models\Affiliate;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AffiliatesResource extends Resource
{
    protected static ?string $model = Affiliate::class;

    protected static ?string $slug = 'affiliates';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Affiliates';

    protected static ?string $navigationGroup = 'Growth';

    protected static ?string $pluralModelLabel = 'Affiliates';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('label')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Account')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'warning'),
                TextColumn::make('visits_count')
                    ->label('Visits')
                    ->counts('visits')
                    ->sortable(),
                TextColumn::make('events_count')
                    ->label('Events')
                    ->counts('events')
                    ->sortable(),
                TextColumn::make('tracked_revenue')
                    ->label('Tracked revenue')
                    ->state(fn (Affiliate $record): string => '$' . number_format(((int) $record->events()->where('type', 'purchase')->sum('revenue_cents')) / 100, 2)),
                TextColumn::make('commission_bps')
                    ->label('Commission')
                    ->state(fn (Affiliate $record): string => number_format(((int) $record->commission_bps) / 100, 1) . '%')
                    ->sortable(),
                TextColumn::make('commission_owed')
                    ->label('Owed')
                    ->state(fn (Affiliate $record): string => '$' . number_format($record->commissionOwedCents((int) $record->events()->where('type', 'purchase')->sum('revenue_cents')) / 100, 2)),
                TextColumn::make('min_payout_cents')
                    ->label('Min payout')
                    ->state(fn (Affiliate $record): string => '$' . number_format(((int) $record->min_payout_cents) / 100, 2))
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
                        'paused' => 'Paused',
                    ]),
            ])
            ->actions([
                Action::make('setCommission')
                    ->label('Set rate')
                    ->icon('heroicon-o-percent-badge')
                    ->fillForm(fn (Affiliate $record): array => [
                        'commission_percent' => ((int) $record->commission_bps) / 100,
                        'min_payout' => ((int) $record->min_payout_cents) / 100,
                    ])
                    ->form([
                        TextInput::make('commission_percent')
                            ->label('Commission %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.1)
                            ->required()
                            ->helperText('e.g., 10 for 10% of tracked revenue'),
                        TextInput::make('min_payout')
                            ->label('Minimum payout (USD)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                    ])
                    ->action(function (Affiliate $record, array $data): void {
                        $record->update([
                            'commission_bps' => (int) round(((float) $data['commission_percent']) * 100),
                            'min_payout_cents' => (int) round(((float) $data['min_payout']) * 100),
                        ]);
                        Notification::make()
                            ->title('Commission updated')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliates::route('/'),
        ];
    }
}
