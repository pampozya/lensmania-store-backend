<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\Product;
use App\Services\FulfillmentService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class OrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 10;

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

                TextInput::make('license_key')
                    ->label('License key')
                    ->maxLength(255),

                TextInput::make('download_url')
                    ->label('Download URL')
                    ->url(),

                Textarea::make('selection_metadata')
                    ->label('Version selection')
                    ->formatStateUsing(fn ($state): string => json_encode($state ?: [], JSON_PRETTY_PRINT))
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.email')->label('Customer')->searchable(),
                TextColumn::make('product_slug')->label('Product')->sortable()->searchable(),
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
                Filter::make('purchased_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Purchased from'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Purchased until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('purchased_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('purchased_at', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('fulfill')
                    ->label('Fulfill')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Fulfill this order?')
                    ->modalDescription('This will create license keys and send the customer their fulfillment email.')
                    ->visible(fn (Order $record): bool => ! in_array($record->api_status, ['fulfilled'], true))
                    ->action(function (Order $record): void {
                        try {
                            app(FulfillmentService::class)->fulfillStaticOrder($record);
                            Notification::make()
                                ->title('Order fulfilled')
                                ->body('License keys created and email sent to ' . $record->user?->email)
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Fulfillment failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
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
