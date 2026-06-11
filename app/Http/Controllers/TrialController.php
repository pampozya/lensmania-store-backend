<?php

namespace App\Http\Controllers;

use App\Services\TrialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrialController extends Controller
{
    public function __construct(private TrialService $trialService)
    {
    }

    public function status(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json($this->trialService->status($user));
    }

    public function start(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_label' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:100'],
            'app_version' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json($this->trialService->start($user, $data), 201);
    }

    public function consume(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_label' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:100'],
            'app_version' => ['nullable', 'string', 'max:100'],
            'minutes_processed' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        return response()->json($this->trialService->consume($user, $data));
    }
}
