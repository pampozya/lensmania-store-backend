<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Services\DownloadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(private DownloadService $downloadService) {}

    public function download(Request $request, int $build): JsonResponse
    {
        $tokenPayload = $this->downloadService->issueToken(
            $request->user(),
            $build,
            $request->userAgent(),
        );

        return response()->json($tokenPayload);
    }

    public function stream(string $token): StreamedResponse|JsonResponse
    {
        return $this->downloadService->stream($token);
    }
}
