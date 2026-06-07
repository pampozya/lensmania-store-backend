<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AuditLogsResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $slug = 'audit-logs';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Admin Action Log';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $pluralModelLabel = 'Admin Action Logs';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('M j, Y H:i')->sortable(),
                TextColumn::make('actor.email')->label('Actor')->searchable(),
                TextColumn::make('event')->label('Event')->badge()->searchable(),
                TextColumn::make('subject_type')->label('Subject')->searchable(),
                TextColumn::make('subject_id')->label('Subject ID')->sortable(),
                TextColumn::make('meta_summary')->label('Metadata')->state(fn (AuditLog $record): string => json_encode($record->meta ?: []))->limit(100),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options(fn (): array => AuditLog::query()->distinct()->pluck('event', 'event')->filter()->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
