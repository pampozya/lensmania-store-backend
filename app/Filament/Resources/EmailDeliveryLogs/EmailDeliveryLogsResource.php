<?php

namespace App\Filament\Resources\EmailDeliveryLogs;

use App\Filament\Resources\EmailDeliveryLogs\Pages\ListEmailDeliveryLogs;
use App\Models\EmailDeliveryLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class EmailDeliveryLogsResource extends Resource
{
    protected static ?string $model = EmailDeliveryLog::class;

    protected static ?string $slug = 'email-delivery-logs';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Email Logs';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $pluralModelLabel = 'Email Logs';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Created')->dateTime('M j, Y H:i')->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('type')->badge()->searchable(),
                TextColumn::make('subject')->limit(60)->searchable(),
                TextColumn::make('provider')->searchable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'delivered', 'sent' => 'success',
                    'failed', 'bounced' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('sent_at')->dateTime('M j, Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'queued' => 'Queued',
                    'sent' => 'Sent',
                    'delivered' => 'Delivered',
                    'failed' => 'Failed',
                    'bounced' => 'Bounced',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailDeliveryLogs::route('/'),
        ];
    }
}
