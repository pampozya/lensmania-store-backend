<?php

namespace App\Http\Controllers\License;

use App\Domain\License\StateMachine\GraceStateMachine;
use App\Http\Controllers\Controller;
use App\Http\Requests\LicenseActivateRequest;
use App\Http\Requests\LicenseValidateRequest;
use App\Http\Requests\LicenseDeactivateRequest;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;

class LicenseController extends Controller
{
    public function __construct(private LicenseService $licenseService) {}

    public function activate(LicenseActivateRequest $request): JsonResponse
    {
        $result = $this->licenseService->activate(
            $request->user(),
            $request->validated('license_key'),
            $request->validated('device_id'),
            $request->validated('platform'),
            $request->validated('app_version')
        );

        return response()->json($result);
    }

    public function validateLicense(LicenseValidateRequest $request): JsonResponse
    {
        $result = $this->licenseService->validate(
            $request->validated('license_key'),
            $request->validated('device_id'),
            $request->validated('platform'),
            $request->validated('app_version'),
            $request->boolean('grace_used'),
            $request->input('grace_started_at'),
        );

        return response()->json($result);
    }

    public function deactivate(LicenseDeactivateRequest $request): JsonResponse
    {
        $this->licenseService->deactivate(
            $request->validated('license_key'),
            $request->validated('device_id')
        );

        return response()->json(['result' => GraceStateMachine::STATE_DEACTIVATED]);
    }
}
