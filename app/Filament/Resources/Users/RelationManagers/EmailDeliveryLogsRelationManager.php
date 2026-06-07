<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EmailDeliveryLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'emailDeliveryLogs';

    protected static ?string $title = 'Email Delivery';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('subject')
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivered', 'sent' => 'success',
                        'failed', 'bounced' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('error')
                    ->label('Error')
                    ->limit(80)
                    ->placeholder('None')
                    ->wrap(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
