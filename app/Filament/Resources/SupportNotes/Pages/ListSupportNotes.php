<?php

namespace App\Filament\Resources\SupportNotes\Pages;

use App\Filament\Resources\SupportNotes\SupportNotesResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

final class ListSupportNotes extends ListRecords
{
    protected static string $resource = SupportNotesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
