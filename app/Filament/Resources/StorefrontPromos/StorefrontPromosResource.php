<?php

namespace App\Filament\Resources\StorefrontPromos;

use App\Models\StorefrontPromo;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
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

    protected static ?string $navigationLabel = 'Promos & Affiliates';

    protected static ?string $navigationGroup = 'Growth & Campaigns';

    protected static ?int $navigationSort = 10;

    protected static ?string $pluralModelLabel = 'Storefront Promos';

    protected static ?string $slug = 'storefront-promos';

    /**
     * Check if this promo is 100% free (price is 0 or discount is 100).
     * Free promos don't require PayPal links.
     */
    private static function isFullyFreePromo(callable $get): bool
    {
        $discountPercent = $get('discount_percent');
        if ($discountPercent == 100) {
            return true;
        }

        $cinecutPrice = (float) ($get('price_cinecut') ?? null);

        return $cinecutPrice === 0.0 && $get('price_cinecut') !== null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Basic Info')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->unique(ignorable: fn ($record) => $record)
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper((string) preg_replace('/\s+/', '', $state)) : $state)
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->placeholder('e.g., YOUSSEFVIP')
                        ->helperText('Leave blank and save to auto-generate a unique code (format: LM-XXXXXXXX)')
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
                ->description('Choose either a discount percent OR a fixed CineCut price. If the price is 0, checkout skips payment and goes straight to order confirmation.')
                ->schema([
                    TextInput::make('discount_percent')
                        ->label('Discount Percent')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->placeholder('e.g., 10 (for 10% off)')
                        ->helperText('If set, applies to all products. Leave empty to use fixed prices instead.'),

                    TextInput::make('price_cinecut')
                        ->label('CineCut Price (USD)')
                        ->numeric()
                        ->step(0.01)
                        ->placeholder('e.g., 15.00')
                        ->helperText('Fixed price override. Leave empty to use discount percent.'),
                ]),

            Section::make('PayPal Links')
                ->columns(1)
                ->description('CineCut PayPal checkout link. Leave empty for 100% free promos.')
                ->schema([
                    TextInput::make('link_cinecut')
                        ->label('CineCut Link')
                        ->placeholder('https://www.paypal.com/ncp/payment/... or just the ID')
                        ->maxLength(500),
                ]),

            Section::make('Expiry')
                ->columns(1)
                ->schema([
                    DateTimePicker::make('expires_at')
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

                TextColumn::make('links_status')
                    ->label('PayPal Links')
                    ->state(fn (StorefrontPromo $record): string => $record->link_cinecut ? 'Complete' : 'Missing')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Complete' ? 'success' : 'danger'),

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
                        // 100% free promos skip PayPal entirely, so they don't need a link.
                        $isFree = $record->discount_percent == 100
                            || ((float) $record->price_cinecut === 0.0
                                && $record->price_cinecut !== null);

                        if (! $record->active
                            && ! $isFree
                            && ! $record->link_cinecut) {
                            Notification::make()
                                ->title('Cannot activate')
                                ->body('Add the CineCut PayPal link before activating this paid promo, or set it to 100% off / $0.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['active' => !$record->active]);
                        Notification::make()
                            ->title($record->active ? 'Promo activated' : 'Promo deactivated')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\StorefrontPromos\Pages\ListStorefrontPromos::route('/'),
            'create' => \App\Filament\Resources\StorefrontPromos\Pages\CreateStorefrontPromo::route('/create'),
            'edit' => \App\Filament\Resources\StorefrontPromos\Pages\EditStorefrontPromo::route('/{record}/edit'),
        ];
    }

    private static function generateUniqueCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'LM-';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (StorefrontPromo::query()->where('code', $code)->exists());

        return $code;
    }
}
