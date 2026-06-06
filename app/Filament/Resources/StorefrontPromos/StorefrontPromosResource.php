<?php

namespace App\Filament\Resources\StorefrontPromos;

use App\Models\StorefrontPromo;
use Filament\Forms\Components\DateTimePickerField;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

final class StorefrontPromosResource extends Resource
{
    protected static ?string $model = StorefrontPromo::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Storefront Promos';

    protected static ?string $pluralModelLabel = 'Storefront Promos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Basic Info')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->unique(ignorable: fn ($record) => $record)
                        ->uppercase()
                        ->placeholder('e.g., YOUSSEFVIP')
                        ->maxLength(255),

                    TextInput::make('label')
                        ->label('Label')
                        ->required()
                        ->placeholder('e.g., Youssef VIP')
                        ->maxLength(255),

                    TextInput::make('affiliate')
                        ->label('Affiliate')
                        ->placeholder('e.g., youssef (optional)')
                        ->maxLength(255),

                    Toggle::make('active')
                        ->label('Active')
                        ->default(true),
                ]),

            Section::make('Discount')
                ->columns(1)
                ->description('Choose either a single discount percent OR set fixed prices for each product.')
                ->schema([
                    TextInput::make('discount_percent')
                        ->label('Discount Percent')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->placeholder('e.g., 10 (for 10% off)')
                        ->helperText('If set, applies to all products. Leave empty to use fixed prices instead.'),

                    TextInput::make('price_hushcut')
                        ->label('HushCut Price (USD)')
                        ->numeric()
                        ->step(0.01)
                        ->placeholder('e.g., 15.00')
                        ->helperText('Fixed price override. Leave empty to use discount percent.'),

                    TextInput::make('price_babelcut')
                        ->label('BabelCut Price (USD)')
                        ->numeric()
                        ->step(0.01)
                        ->placeholder('e.g., 15.00'),

                    TextInput::make('price_bundle')
                        ->label('Studio Pass Price (USD)')
                        ->numeric()
                        ->step(0.01)
                        ->placeholder('e.g., 25.00'),
                ]),

            Section::make('PayPal Links')
                ->columns(1)
                ->schema([
                    TextInput::make('link_hushcut')
                        ->label('HushCut Link')
                        ->placeholder('https://www.paypal.com/ncp/payment/8Z3B74X38WYHY or just the ID')
                        ->maxLength(500),

                    TextInput::make('link_babelcut')
                        ->label('BabelCut Link')
                        ->placeholder('https://www.paypal.com/ncp/payment/... or just the ID')
                        ->maxLength(500),

                    TextInput::make('link_bundle')
                        ->label('Studio Pass Link')
                        ->placeholder('https://www.paypal.com/ncp/payment/... or just the ID')
                        ->maxLength(500),
                ]),

            Section::make('Expiry')
                ->columns(1)
                ->schema([
                    DateTimePickerField::make('expires_at')
                        ->label('Expires At')
                        ->placeholder('Leave empty for no expiry')
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('affiliate')
                    ->label('Affiliate')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('discount_percent')
                    ->label('Discount %')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '—')
                    ->sortable(),

                ToggleColumn::make('active')
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M j, Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('toggle')
                    ->label(fn (StorefrontPromo $record) => $record->active ? 'Deactivate' : 'Activate')
                    ->icon(fn (StorefrontPromo $record) => $record->active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (StorefrontPromo $record) => $record->active ? 'warning' : 'success')
                    ->action(function (StorefrontPromo $record): void {
                        $record->update(['active' => !$record->active]);
                        Notification::make()
                            ->title($record->active ? 'Promo activated' : 'Promo deactivated')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ]);
    }
}
