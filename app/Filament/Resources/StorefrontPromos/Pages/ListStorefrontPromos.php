<?php

namespace App\Filament\Resources\StorefrontPromos\Pages;

use App\Filament\Resources\StorefrontPromos\StorefrontPromosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStorefrontPromos extends ListRecords
{
    protected static string $resource = StorefrontPromosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
