<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class OrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $pluralModelLabel = 'Orders';

    public static function form($form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Select::make('api_status')
                    ->label('Fulfillment status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'fulfilled' => 'Fulfilled',
                    ])
                    ->default('paid')
                    ->required(),

                Select::make('status')
                    ->label('Payment status')
                    ->options([
                        'created' => 'Created',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->default('paid')
                    ->required(),

                Select::make('user_id')
                    ->label('Customer')
                    ->relationship('user', 'email')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} ({$record->email})")
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->required(),

                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set, Get $get): void {
                        $product = $state ? Product::query()->find($state) : null;

                        if (! $product) {
                            return;
                        }

                        $set('product_slug', $product->slug);

                        if (! $get('amount_cents')) {
                            $set('amount_cents', $product->price_cents);
                            $set('amount_usd', number_format($product->price_cents / 100, 2, '.', ''));
                        }
                    })
                    ->required(),

                TextInput::make('product_slug')
                    ->label('Product slug')
                    ->required()
                    ->maxLength(255),

                TextInput::make('amount_cents')
                    ->label('Amount cents')
                    ->numeric()
                    ->required()
                    ->minValue(0),

                TextInput::make('amount_usd')
                    ->label('Amount USD')
                    ->numeric()
                    ->required()
                    ->minValue(0),

                TextInput::make('currency')
                    ->label('Currency')
                    ->default('USD')
                    ->required()
                    ->maxLength(3),

                TextInput::make('promo_code')
                    ->label('Promo code')
                    ->maxLength(255),

                TextInput::make('paypal_payment_id')
                    ->label('PayPal payment ID')
                    ->maxLength(255),

                DateTimePicker::make('paid_at')
                    ->label('Paid at'),

                DateTimePicker::make('purchased_at')
                    ->label('Purchased at')
                    ->default(now()),

                Textarea::make('license_key')
                    ->label('License key')
                    ->rows(2)
                    ->columnSpanFull(),

                TextInput::make('download_url')
                    ->label('Download URL')
                    ->url(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.email')->label('Customer')->searchable(),
                TextColumn::make('product_slug')->label('Product')->sortable(),
                TextColumn::make('amount_usd')->label('Amount USD')->money('usd'),
                TextColumn::make('promo_code')->label('Promo'),
                TextColumn::make('api_status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'fulfilled' => 'success',
                    'paid' => 'warning',
                    default => 'gray',
                }),
                TextColumn::make('purchased_at')->dateTime('M j, Y'),
            ])
            ->defaultSort('purchased_at', 'desc')
            ->filters([
                SelectFilter::make('api_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'fulfilled' => 'Fulfilled',
                    ]),
            ])
            ->actions([
                Action::make('mark_fulfilled')
                    ->label('Mark fulfilled')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        $record->forceFill(['api_status' => 'fulfilled', 'status' => 'paid'])->save();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
