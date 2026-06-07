<?php

namespace App\Filament\Resources\EmailDeliveryLogs\Pages;

use App\Filament\Resources\EmailDeliveryLogs\EmailDeliveryLogsResource;
use Filament\Resources\Pages\ListRecords;

final class ListEmailDeliveryLogs extends ListRecords
{
    protected static string $resource = EmailDeliveryLogsResource::class;
}
