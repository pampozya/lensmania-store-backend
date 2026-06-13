<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\SiteVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteVisitController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['nullable', 'string', 'max:128'],
            'path' => ['nullable', 'string', 'max:255'],
            'landing_path' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:255'],
            'promo_code' => ['nullable', 'string', 'max:64'],
            'affiliate' => ['nullable', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'max:64'],
            'region' => ['nullable', 'string', 'max:128'],
            'city' => ['nullable', 'string', 'max:128'],
            'device_type' => ['nullable', 'string', 'max:32'],
            'browser' => ['nullable', 'string', 'max:64'],
            'os' => ['nullable', 'string', 'max:64'],
            'screen' => ['nullable', 'string', 'max:64'],
            'language' => ['nullable', 'string', 'max:32'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $userAgent = (string) $request->userAgent();
        $sessionId = (string) ($data['session_id'] ?? '');
        $ip = (string) $request->ip();

        SiteVisit::create([
            'visitor_hash' => $sessionId !== '' ? hash('sha256', $sessionId . '|' . config('app.key')) : null,
            'ip_hash' => $ip !== '' ? hash('sha256', $ip . '|' . config('app.key')) : null,
            'path' => $data['path'] ?? null,
            'landing_path' => $data['landing_path'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'promo_code' => $this->upper($data['promo_code'] ?? null),
            'affiliate' => $data['affiliate'] ?? null,
            'country' => $this->locationHeader($request, 'country') ?: ($data['country'] ?? null),
            'region' => $this->locationHeader($request, 'region') ?: ($data['region'] ?? null),
            'city' => $this->locationHeader($request, 'city') ?: ($data['city'] ?? null),
            'device_type' => $data['device_type'] ?? $this->deviceType($userAgent),
            'browser' => $data['browser'] ?? $this->browser($userAgent),
            'os' => $data['os'] ?? $this->os($userAgent),
            'screen' => $data['screen'] ?? null,
            'language' => $data['language'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'user_agent' => $userAgent !== '' ? substr($userAgent, 0, 1000) : null,
        ]);

        return response()->json(['ok' => true], 201);
    }

    private function upper(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : strtoupper($value);
    }

    private function locationHeader(Request $request, string $field): ?string
    {
        $headers = match ($field) {
            'country' => ['CF-IPCountry', 'X-Country-Code', 'X-Geo-Country'],
            'region' => ['X-Geo-Region', 'X-Region-Code'],
            'city' => ['X-Geo-City', 'X-City'],
            default => [],
        };

        foreach ($headers as $header) {
            $value = trim((string) $request->headers->get($header));

            if ($value !== '' && strtoupper($value) !== 'XX') {
                return substr($value, 0, 128);
            }
        }

        return null;
    }

    private function deviceType(string $userAgent): string
    {
        if (preg_match('/ipad|tablet/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/mobi|iphone|android/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') && ! str_contains($userAgent, 'Chromium/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome/') => 'Safari',
            default => 'Other',
        };
    }

    private function os(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Win') => 'Other',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Other',
        };
    }
}
