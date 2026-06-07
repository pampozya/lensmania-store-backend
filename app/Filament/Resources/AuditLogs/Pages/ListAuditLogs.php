<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogsResource;
use Filament\Resources\Pages\ListRecords;

final class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogsResource::class;
}
