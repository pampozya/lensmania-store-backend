<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\SiteEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteEventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['nullable', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:80'],
            'path' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:255'],
            'product_slug' => ['nullable', 'string', 'max:64'],
            'promo_code' => ['nullable', 'string', 'max:64'],
            'affiliate' => ['nullable', 'string', 'max:64'],
            'value' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'max:3'],
            'country' => ['nullable', 'string', 'max:64'],
            'city' => ['nullable', 'string', 'max:128'],
            'device_type' => ['nullable', 'string', 'max:32'],
            'browser' => ['nullable', 'string', 'max:64'],
            'os' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
        ]);

        $sessionId = (string) ($data['session_id'] ?? '');
        $ip = (string) $request->ip();

        SiteEvent::create([
            'visitor_hash' => $sessionId !== '' ? hash('sha256', $sessionId . '|' . config('app.key')) : null,
            'ip_hash' => $ip !== '' ? hash('sha256', $ip . '|' . config('app.key')) : null,
            'name' => $data['name'],
            'path' => $data['path'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'product_slug' => $data['product_slug'] ?? null,
            'promo_code' => $this->upper($data['promo_code'] ?? null),
            'affiliate' => $data['affiliate'] ?? null,
            'value' => $data['value'] ?? null,
            'currency' => isset($data['currency']) ? strtoupper($data['currency']) : null,
            'country' => $this->locationHeader($request, 'country') ?: ($data['country'] ?? null),
            'city' => $this->locationHeader($request, 'city') ?: ($data['city'] ?? null),
            'device_type' => $data['device_type'] ?? null,
            'browser' => $data['browser'] ?? null,
            'os' => $data['os'] ?? null,
            'metadata' => $data['metadata'] ?? null,
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
}
