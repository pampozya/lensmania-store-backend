<?php

namespace App\Filament\Resources\SupportNotes\Pages;

use App\Filament\Resources\SupportNotes\SupportNotesResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSupportNote extends CreateRecord
{
    protected static string $resource = SupportNotesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['admin_user_id'] = auth()->id();

        return $data;
    }
}
