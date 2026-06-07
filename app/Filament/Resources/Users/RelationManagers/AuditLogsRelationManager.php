<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\AuditLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'timelineAuditLogs';

    protected static ?string $title = 'Timeline / Audit Log';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('actor.email')
                    ->label('Actor')
                    ->placeholder('System')
                    ->searchable(),
                Tables\Columns\TextColumn::make('meta_summary')
                    ->label('Metadata')
                    ->state(fn (AuditLog $record): string => json_encode($record->meta ?: []))
                    ->limit(120)
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
