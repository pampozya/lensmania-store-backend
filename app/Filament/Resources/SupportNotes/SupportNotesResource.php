<?php

namespace App\Filament\Resources\SupportNotes;

use App\Filament\Resources\SupportNotes\Pages\CreateSupportNote;
use App\Filament\Resources\SupportNotes\Pages\EditSupportNote;
use App\Filament\Resources\SupportNotes\Pages\ListSupportNotes;
use App\Models\SupportNote;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class SupportNotesResource extends Resource
{
    protected static ?string $model = SupportNote::class;

    protected static ?string $slug = 'support-notes';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Support Inbox Lite';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $pluralModelLabel = 'Support Notes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('user_id')
                ->label('Customer')
                ->relationship('user', 'email')
                ->searchable()
                ->preload(),
            Select::make('order_id')
                ->label('Order')
                ->relationship('order', 'id')
                ->searchable(),
            Select::make('license_id')
                ->label('License')
                ->relationship('license', 'license_key')
                ->searchable(),
            Select::make('category')
                ->options([
                    'general' => 'General',
                    'payment' => 'Payment',
                    'license' => 'License',
                    'download' => 'Download',
                    'device_reset' => 'Device reset',
                    'refund' => 'Refund',
                ])
                ->default('general')
                ->required(),
            Select::make('status')
                ->options([
                    'open' => 'Open',
                    'waiting' => 'Waiting',
                    'resolved' => 'Resolved',
                ])
                ->default('open')
                ->required(),
            Textarea::make('body')
                ->label('Note')
                ->rows(6)
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Created')->dateTime('M j, Y H:i')->sortable(),
                TextColumn::make('user.email')->label('Customer')->searchable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'resolved' => 'success',
                    'waiting' => 'warning',
                    default => 'primary',
                }),
                TextColumn::make('body')->label('Note')->limit(90)->searchable(),
                TextColumn::make('admin.email')->label('Admin')->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(['open' => 'Open', 'waiting' => 'Waiting', 'resolved' => 'Resolved']),
                SelectFilter::make('category')->options([
                    'general' => 'General',
                    'payment' => 'Payment',
                    'license' => 'License',
                    'download' => 'Download',
                    'device_reset' => 'Device reset',
                    'refund' => 'Refund',
                ]),
            ])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportNotes::route('/'),
            'create' => CreateSupportNote::route('/create'),
            'edit' => EditSupportNote::route('/{record}/edit'),
        ];
    }
}
