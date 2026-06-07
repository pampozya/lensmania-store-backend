<?php

namespace App\Filament\Resources\SiteVisits;

use App\Filament\Resources\SiteVisits\Pages\ListSiteVisits;
use App\Models\SiteVisit;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only viewer of the raw visit log: who (anonymised visitor), when,
 * what page, from where, on what device. Privacy: visitor/IP are stored hashed,
 * so this shows a short visitor token, never a raw IP.
 */
final class SiteVisitsResource extends Resource
{
    protected static ?string $model = SiteVisit::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'site-visits';

    protected static ?string $navigationIcon = 'heroicon-o-cursor-arrow-ripple';

    protected static ?string $navigationLabel = 'Visitor Log';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?string $pluralModelLabel = 'Visitor Log';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable(),

                TextColumn::make('visitor_hash')
                    ->label('Visitor')
                    ->formatStateUsing(fn (?string $state): string => $state ? substr($state, 0, 10) : '—')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('path')
                    ->label('Page')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('location')
                    ->label('Location')
                    ->state(fn (SiteVisit $r): string => trim(implode(', ', array_filter([$r->city, $r->region, $r->country]))) ?: 'Unknown')
                    ->searchable(query: fn (Builder $q, string $search): Builder => $q
                        ->where('city', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%")
                        ->orWhere('region', 'like', "%{$search}%")),

                TextColumn::make('device')
                    ->label('Device')
                    ->state(fn (SiteVisit $r): string => trim(implode(' / ', array_filter([$r->device_type, $r->os, $r->browser]))) ?: 'Unknown'),

                TextColumn::make('referrer')
                    ->label('Referrer')
                    ->limit(30)
                    ->tooltip(fn (SiteVisit $r): ?string => $r->referrer)
                    ->placeholder('direct')
                    ->toggleable(),

                TextColumn::make('promo_code')
                    ->label('Promo')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('affiliate')
                    ->label('Affiliate')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('device_type')
                    ->label('Device type')
                    ->options([
                        'desktop' => 'Desktop',
                        'mobile' => 'Mobile',
                        'tablet' => 'Tablet',
                    ]),

                Filter::make('date')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteVisits::route('/'),
        ];
    }
}
