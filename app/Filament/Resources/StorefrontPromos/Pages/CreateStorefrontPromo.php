<?php

namespace App\Filament\Resources\StorefrontPromos\Pages;

use App\Filament\Resources\StorefrontPromos\StorefrontPromosResource;
use App\Models\StorefrontPromo;
use Filament\Resources\Pages\CreateRecord;

class CreateStorefrontPromo extends CreateRecord
{
    protected static string $resource = StorefrontPromosResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate code if empty
        if (empty($data['code'])) {
            $data['code'] = $this->generateUniqueCode();
        }

        return $data;
    }

    private function generateUniqueCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'LM-';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (StorefrontPromo::where('code', $code)->exists());

        return $code;
    }
}
