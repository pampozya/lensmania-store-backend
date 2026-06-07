<?php

namespace App\Filament\Resources\DownloadTokens\Pages;

use App\Filament\Resources\DownloadTokens\DownloadTokensResource;
use Filament\Resources\Pages\ListRecords;

final class ListDownloadTokens extends ListRecords
{
    protected static string $resource = DownloadTokensResource::class;
}
