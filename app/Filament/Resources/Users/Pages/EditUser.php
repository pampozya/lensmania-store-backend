<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UsersResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UsersResource::class;

    public function getTitle(): string
    {
        $email = $this->record->email ?: 'Customer';

        return "Customer: {$email}";
    }
}
