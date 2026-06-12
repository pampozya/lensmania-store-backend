<?php

namespace App\Filament\Resources\Trials;

use App\Filament\Resources\Trials\Pages\ListTrials;
use App\Models\Trial;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TrialsResource extends Resource
{
    protected static ?string $model = Trial::class;

    protected static ?string $slug = 'trials';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Trials';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 45;

    protected static ?string $pluralModelLabel = 'Trials';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label('Customer')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->placeholder('Trial'),

                TextColumn::make('computed_status')
                    ->label('Status')
                    ->state(fn (Trial $record): string => self::computedStatus($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'converted' => 'success',
                        'expired' => 'danger',
                        'limit_reached' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('jobs_usage')
                    ->label('Cuts')
                    ->state(fn (Trial $record): string => $record->jobs_used . '/' . $record->jobs_limit),

                TextColumn::make('minutes_usage')
                    ->label('Minutes')
                    ->state(fn (Trial $record): string => $record->minutes_used . '/' . $record->minutes_limit),

                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),

                TextColumn::make('converted_at')
                    ->label('Converted')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder('Not converted'),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->placeholder('Unknown'),

                TextColumn::make('app_version')
                    ->label('App version')
                    ->placeholder('Unknown'),

                TextColumn::make('device_id')
                    ->label('Device')
                    ->limit(18)
                    ->copyable()
                    ->placeholder('Not assigned'),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'limit_reached' => 'Limit reached',
                        'converted' => 'Converted',
                    ]),

                Filter::make('ended')
                    ->label('Ended only')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('converted_at')
                        ->where('status', '!=', 'converted')
                        ->where(function (Builder $query): void {
                            $query->whereIn('status', ['expired', 'limit_reached'])
                                ->orWhere('expires_at', '<', now())
                                ->orWhereColumn('jobs_used', '>=', 'jobs_limit')
                                ->orWhereColumn('minutes_used', '>=', 'minutes_limit');
                        })),

                Filter::make('converted')
                    ->label('Converted only')
                    ->query(fn (Builder $query): Builder => $query
                        ->where(function (Builder $query): void {
                            $query->where('status', 'converted')
                                ->orWhereNotNull('converted_at');
                        })),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrials::route('/'),
        ];
    }

    private static function computedStatus(Trial $record): string
    {
        if ($record->status === 'converted' || $record->converted_at !== null) {
            return 'converted';
        }

        if ($record->expires_at?->isPast()) {
            return 'expired';
        }

        if ($record->jobs_used >= $record->jobs_limit || $record->minutes_used >= $record->minutes_limit) {
            return 'limit_reached';
        }

        return $record->status;
    }
}
