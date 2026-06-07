<?php

namespace App\Filament\Resources\SupportNotes\Pages;

use App\Filament\Resources\SupportNotes\SupportNotesResource;
use Filament\Resources\Pages\EditRecord;

final class EditSupportNote extends EditRecord
{
    protected static string $resource = SupportNotesResource::class;
}
