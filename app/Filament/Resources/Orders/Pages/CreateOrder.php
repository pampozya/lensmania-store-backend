<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrdersResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrdersResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $product = Product::query()->find($data['product_id'] ?? null);

        if ($product) {
            $data['product_slug'] = $data['product_slug'] ?: $product->slug;
        }

        $data['currency'] = strtoupper($data['currency'] ?? 'USD');
        $data['amount_cents'] = (int) $data['amount_cents'];
        $data['amount_usd'] = round($data['amount_cents'] / 100, 2);

        if (($data['status'] ?? null) === 'paid' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        if (empty($data['purchased_at'])) {
            $data['purchased_at'] = now();
        }

        return $data;
    }
}
