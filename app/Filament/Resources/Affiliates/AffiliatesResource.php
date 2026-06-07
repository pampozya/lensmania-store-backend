<?php

namespace App\Filament\Resources\Affiliates;

use App\Filament\Resources\Affiliates\Pages\ListAffiliates;
use App\Models\Affiliate;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AffiliatesResource extends Resource
{
    protected static ?string $model = Affiliate::class;

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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliates::route('/'),
        ];
    }
}
