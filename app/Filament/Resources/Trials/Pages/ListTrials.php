<?php

namespace App\Filament\Resources\Trials\Pages;

use App\Filament\Resources\Trials\TrialsResource;
use Filament\Resources\Pages\ListRecords;

final class ListTrials extends ListRecords
{
    protected static string $resource = TrialsResource::class;
}
