<?php

namespace App\Filament\Resources\StorefrontPromos\Pages;

use App\Filament\Resources\StorefrontPromos\StorefrontPromosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStorefrontPromo extends EditRecord
{
    protected static string $resource = StorefrontPromosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
