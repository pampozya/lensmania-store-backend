<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SupportNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'supportNotes';

    protected static ?string $title = 'Support Notes';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('admin_user_id')
                ->default(fn (): ?int => auth()->id()),
            Forms\Components\Select::make('category')
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
            Forms\Components\Select::make('status')
                ->options([
                    'open' => 'Open',
                    'waiting' => 'Waiting',
                    'resolved' => 'Resolved',
                ])
                ->default('open')
                ->required(),
            Forms\Components\Textarea::make('body')
                ->label('Note')
                ->rows(5)
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'resolved' => 'success',
                        'waiting' => 'warning',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('body')
                    ->label('Note')
                    ->limit(120)
                    ->wrap(),
                Tables\Columns\TextColumn::make('admin.email')
                    ->label('Admin')
                    ->placeholder('System'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add support note'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
