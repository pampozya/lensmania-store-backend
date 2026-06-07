<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Orders\OrdersResource;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Orders';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_slug')
                    ->label('Product')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount_usd')
                    ->label('Amount')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('promo_code')
                    ->label('Promo')
                    ->placeholder('None')
                    ->badge(),
                Tables\Columns\TextColumn::make('api_status')
                    ->label('Fulfillment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fulfilled' => 'success',
                        'paid' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('purchased_at')
                    ->label('Purchased')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('purchased_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('open_order')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Order $record): string => OrdersResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
