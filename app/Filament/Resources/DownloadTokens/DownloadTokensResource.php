<?php

namespace App\Filament\Resources\DownloadTokens;

use App\Filament\Resources\DownloadTokens\Pages\ListDownloadTokens;
use App\Models\DownloadToken;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class DownloadTokensResource extends Resource
{
    protected static ?string $model = DownloadToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Download Delivery Logs';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $pluralModelLabel = 'Download Delivery Logs';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('token')
                    ->label('Token')
                    ->limit(16)
                    ->copyable()
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('build.product.name')
                    ->label('Product'),
                TextColumn::make('build.platform')
                    ->label('Platform')
                    ->badge(),
                TextColumn::make('build.version')
                    ->label('Version'),
                TextColumn::make('delivery_status')
                    ->label('Status')
                    ->state(function (DownloadToken $record): string {
                        if ($record->used_at) {
                            return 'used';
                        }

                        return $record->expires_at?->isPast() ? 'expired' : 'active';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'used' => 'gray',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('used_at')
                    ->label('Used')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Issued')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('unused')
                    ->label('Unused only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('used_at')),
                Filter::make('expired')
                    ->label('Expired only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('used_at')->where('expires_at', '<', now())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDownloadTokens::route('/'),
        ];
    }
}
