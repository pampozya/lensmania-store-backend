<?php

namespace App\Services;

use Illuminate\Support\Str;

class DownloadService
{
    public function __construct(private AuditService $auditService)
    {
    }

    public function issueToken($user, int $buildId, ?string $userAgent = null): array
    {
        $this->auditService->logEvent('download_token_issue', null, ['build_id' => $buildId]);

        return [
            'token' => (string) Str::uuid(),
            'url' => '/download/' . Str::uuid(),
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
        ];
    }

    public function stream(string $token)
    {
        $this->auditService->logEvent('download_token_used', null, ['token' => $token]);

        return response()->json(['ok' => true, 'message' => 'download stream placeholder'], 200);
    }
}
