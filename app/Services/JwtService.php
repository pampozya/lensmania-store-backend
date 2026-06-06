<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

class JwtService
{
    private const HEADER = ['typ' => 'JWT', 'alg' => 'HS256'];
    private const DEFAULT_TTL_MINUTES = 90 * 24 * 60;

    private string $secret;

    public function __construct()
    {
        $secret = (string) env('JWT_SECRET', '');
        if ($secret === '') {
            throw new RuntimeException('JWT_SECRET is required.');
        }

        $this->secret = $secret;
    }

    public function issue(User $user, int $ttlMinutes = self::DEFAULT_TTL_MINUTES): string
    {
        $now = time();
        $payload = [
            'iss' => config('app.name'),
            'sub' => (string) $user->id,
            'email' => (string) $user->email,
            'iat' => $now,
            'exp' => $now + ($ttlMinutes * 60),
        ];

        $base64Header = $this->base64UrlEncode(json_encode(self::HEADER, JSON_UNESCAPED_UNICODE));
        $base64Payload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $base64Header . '.' . $base64Payload, $this->secret, true));

        return implode('.', [$base64Header, $base64Payload, $signature]);
    }

    public function parse(string $token): ?array
    {
        $parts = explode('.', (string) $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true));

        if (! hash_equals($expectedSignature, $encodedSignature)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($encodedPayload);
        if ($payloadJson === false) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (! is_array($payload)) {
            return null;
        }

        if (empty($payload['sub']) || ! is_scalar($payload['sub'])) {
            return null;
        }

        if (empty($payload['exp']) || ! is_numeric($payload['exp']) || ((int) $payload['exp']) < time()) {
            return null;
        }

        return $payload;
    }

    public function identifyUser(string $token): ?User
    {
        $payload = $this->parse($token);
        if (! is_array($payload)) {
            return null;
        }

        $user = User::query()->find((int) $payload['sub']);
        if (! $user) {
            return null;
        }

        return $user;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): false|string
    {
        $normalized = strtr($value, '-_', '+/');
        $padLen = strlen($normalized) % 4;
        if ($padLen !== 0) {
            $normalized .= str_repeat('=', 4 - $padLen);
        }

        return base64_decode($normalized, true);
    }
}
