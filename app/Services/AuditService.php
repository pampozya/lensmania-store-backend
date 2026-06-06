<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function logEvent(string $event, ?Model $subject = null, array $meta = []): void
    {
        AuditLog::create([
            'event' => $event,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'ip_hash' => hash('sha256', Request::createFromGlobals()->ip()),
            'meta' => $meta,
        ]);
    }
}
