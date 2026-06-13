<?php

namespace App\Services;

use App\Models\Entitlement;
use App\Models\License;
use App\Models\Product;
use App\Models\Trial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrialService
{
    private const DURATION_DAYS = 3;
    private const JOBS_LIMIT = 3;
    private const MINUTES_LIMIT = 60;

    public function start(User $user, array $metadata = []): array
    {
        if ($this->hasPaidAccess($user)) {
            $trial = Trial::query()->where('user_id', $user->id)->first();
            if ($trial) {
                $this->markConverted($user);
            }

            return $this->status($user);
        }

        return DB::transaction(function () use ($user, $metadata): array {
            $existing = Trial::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw ValidationException::withMessages([
                    'trial' => 'Trial has already been started for this account.',
                ]);
            }

            $product = Product::query()->where('slug', 'cinecut')->first();

            $trial = Trial::create([
                'user_id' => $user->id,
                'product_id' => $product?->id,
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => now()->addDays(self::DURATION_DAYS),
                'jobs_used' => 0,
                'jobs_limit' => self::JOBS_LIMIT,
                'minutes_used' => 0,
                'minutes_limit' => self::MINUTES_LIMIT,
                'device_id' => $this->stringOrNull($metadata['device_id'] ?? null),
                'device_label' => $this->stringOrNull($metadata['device_label'] ?? null),
                'platform' => $this->stringOrNull($metadata['platform'] ?? null),
                'app_version' => $this->stringOrNull($metadata['app_version'] ?? null),
            ]);

            $this->ensureTrialLicense($user, $trial->fresh());

            return $this->format($trial->fresh(), false);
        });
    }

    public function status(User $user): array
    {
        $trial = Trial::query()->where('user_id', $user->id)->first();
        $paidAccess = $this->hasPaidAccess($user);

        if ($trial === null) {
            return $this->emptyStatus($paidAccess);
        }

        if ($paidAccess && $trial->converted_at === null) {
            $trial = $this->markConverted($user) ?? $trial->fresh();
        }

        $this->refreshComputedStatus($trial);
        $trial = $trial->fresh();
        if (! $paidAccess) {
            $this->ensureTrialLicense($user, $trial);
        }

        return $this->format($trial->fresh(), $paidAccess);
    }

    public function consume(User $user, array $payload): array
    {
        return DB::transaction(function () use ($user, $payload): array {
            $trial = Trial::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($trial === null) {
                throw ValidationException::withMessages([
                    'trial' => 'No trial has been started for this account.',
                ]);
            }

            if ($this->hasPaidAccess($user)) {
                $this->markConverted($user);

                return $this->format($trial->fresh(), true);
            }

            $this->refreshComputedStatus($trial);
            $trial = $trial->fresh();

            if ($trial->status !== 'active') {
                return $this->format($trial, false);
            }

            $deviceId = $this->stringOrNull($payload['device_id'] ?? null);
            if ($deviceId !== null) {
                if ($trial->device_id === null) {
                    $trial->forceFill([
                        'device_id' => $deviceId,
                        'device_label' => $this->stringOrNull($payload['device_label'] ?? null) ?? $trial->device_label,
                        'platform' => $this->stringOrNull($payload['platform'] ?? null) ?? $trial->platform,
                        'app_version' => $this->stringOrNull($payload['app_version'] ?? null) ?? $trial->app_version,
                    ])->save();
                } elseif ($trial->device_id !== $deviceId) {
                    throw ValidationException::withMessages([
                        'device_id' => 'Trial is already assigned to another device.',
                    ]);
                }
            }

            $minutes = max(0, (int) ($payload['minutes_processed'] ?? 0));

            $trial->forceFill([
                'jobs_used' => $trial->jobs_used + 1,
                'minutes_used' => $trial->minutes_used + $minutes,
            ])->save();

            $this->refreshComputedStatus($trial);

            return $this->format($trial->fresh(), false);
        });
    }

    public function markConverted(User $user): ?Trial
    {
        return $this->markConvertedForUserId((int) $user->id);
    }

    public function markConvertedForUserId(int $userId): ?Trial
    {
        $trial = Trial::query()->where('user_id', $userId)->first();
        if ($trial === null) {
            return null;
        }

        if ($trial->converted_at === null || $trial->status !== 'converted') {
            $trial->forceFill([
                'status' => 'converted',
                'converted_at' => $trial->converted_at ?? now(),
            ])->save();
        }

        License::query()
            ->where('user_id', $userId)
            ->where('kind', 'trial')
            ->update(['status' => 'revoked']);

        return $trial->fresh();
    }

    private function refreshComputedStatus(Trial $trial): void
    {
        if ($trial->status === 'converted') {
            return;
        }

        $newStatus = $trial->status;
        $limitReachedAt = $trial->limit_reached_at;

        if ($trial->expires_at->isPast()) {
            $newStatus = 'expired';
        } elseif ($trial->jobs_used >= $trial->jobs_limit || $trial->minutes_used >= $trial->minutes_limit) {
            $newStatus = 'limit_reached';
            $limitReachedAt = $limitReachedAt ?? now();
        } else {
            $newStatus = 'active';
        }

        if ($newStatus !== $trial->status || (string) $limitReachedAt !== (string) $trial->limit_reached_at) {
            $trial->forceFill([
                'status' => $newStatus,
                'limit_reached_at' => $limitReachedAt,
            ])->save();
        }

        if ($newStatus !== 'active') {
            License::query()
                ->where('user_id', $trial->user_id)
                ->where('kind', 'trial')
                ->update(['status' => 'revoked']);
        }
    }

    private function hasPaidAccess(User $user): bool
    {
        return Entitlement::query()
            ->where('user_id', $user->id)
            ->where('active', true)
            ->exists();
    }

    private function ensureTrialLicense(User $user, Trial $trial): ?License
    {
        $product = $trial->product ?: Product::query()->where('slug', 'cinecut')->first();
        if (! $product) {
            return null;
        }

        if ((int) $trial->product_id !== (int) $product->id) {
            $trial->forceFill(['product_id' => $product->id])->save();
        }

        $license = License::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('kind', 'trial')
            ->first();

        if (! $license) {
            $license = License::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'license_key' => self::trialLicenseKey($user->id, $trial->id),
                'kind' => 'trial',
                'status' => 'active',
                'expires_at' => $trial->expires_at,
            ]);
        } else {
            $license->forceFill([
                'status' => $trial->status === 'active' ? 'active' : 'revoked',
                'expires_at' => $trial->expires_at,
            ])->save();
        }

        return $license->fresh();
    }

    private static function trialLicenseKey(int $userId, int $trialId): string
    {
        $random = strtoupper(substr(hash('sha256', $userId . ':' . $trialId . ':' . config('app.key')), 0, 10));

        return 'LM-CINECUT-TRIAL-' . $random;
    }

    private function emptyStatus(bool $paidAccess): array
    {
        return [
            'status' => $paidAccess ? 'converted' : 'not_started',
            'active' => false,
            'allowed' => $paidAccess,
            'paid_access' => $paidAccess,
            'started_at' => null,
            'expires_at' => null,
            'jobs_used' => 0,
            'jobs_limit' => self::JOBS_LIMIT,
            'jobs_remaining' => self::JOBS_LIMIT,
            'minutes_used' => 0,
            'minutes_limit' => self::MINUTES_LIMIT,
            'minutes_remaining' => self::MINUTES_LIMIT,
            'device_id' => null,
            'upgrade_required' => ! $paidAccess,
            'converted_at' => null,
        ];
    }

    private function format(Trial $trial, bool $paidAccess): array
    {
        $active = $trial->status === 'active';
        $allowed = $paidAccess || $active;
        $license = License::query()
            ->where('user_id', $trial->user_id)
            ->where('product_id', $trial->product_id)
            ->where('kind', 'trial')
            ->first();

        return [
            'status' => $trial->status,
            'active' => $active,
            'allowed' => $allowed,
            'paid_access' => $paidAccess,
            'started_at' => $trial->started_at?->toIso8601String(),
            'expires_at' => $trial->expires_at?->toIso8601String(),
            'jobs_used' => $trial->jobs_used,
            'jobs_limit' => $trial->jobs_limit,
            'jobs_remaining' => max(0, $trial->jobs_limit - $trial->jobs_used),
            'minutes_used' => $trial->minutes_used,
            'minutes_limit' => $trial->minutes_limit,
            'minutes_remaining' => max(0, $trial->minutes_limit - $trial->minutes_used),
            'device_id' => $trial->device_id,
            'license_key' => $license?->license_key,
            'license_kind' => 'trial',
            'download_url' => $this->downloadUrl($trial),
            'upgrade_required' => ! $paidAccess && ! $active,
            'converted_at' => $trial->converted_at?->toIso8601String(),
        ];
    }

    private function downloadUrl(Trial $trial): ?string
    {
        $config = config('downloads.products.cinecut');
        if (! is_array($config)) {
            return null;
        }

        $platform = $trial->platform ?: 'mac-arm64';
        $app = 'premiere';

        return $config['variants'][$platform][$app]
            ?? $config['variants']['mac-arm64'][$app]
            ?? $config['url']
            ?? null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
