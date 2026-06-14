<?php

namespace App\Services;

use App\Domain\License\StateMachine\GraceStateMachine;
use App\Models\License;
use App\Models\LicenseDevice;
use App\Models\LicenseGrace;
use App\Models\User;

class LicenseService
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    private const MAX_DEVICES = 2;

    public static function generate(string $product, ?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $random = self::randomBase32(8);
        $checksum = self::checksum($product . $year . $random);

        return sprintf(
            'LM-%s-%d-%s-%s',
            strtoupper($product),
            $year,
            $random,
            $checksum
        );
    }

    private static function randomBase32(int $length): string
    {
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= self::ALPHABET[random_int(0, 31)];
        }

        return $result;
    }

    private static function checksum(string $input): string
    {
        $sum = 0;

        foreach (str_split($input) as $char) {
            $sum += ord($char);
        }

        $check1 = self::ALPHABET[$sum % 32];
        $check2 = self::ALPHABET[($sum >> 5) % 32];

        return $check1 . $check2;
    }

    public function activate(?User $user, string $licenseKey, string $deviceId, string $platform, string $appVersion): array
    {
        $license = $this->findActiveLicense($licenseKey, $user);

        if (! $license) {
            return $this->denied(GraceStateMachine::STATE_NO_RECORD, 'License not found or inactive.');
        }

        $activeDevices = LicenseDevice::query()
            ->where('license_id', $license->id)
            ->where('status', 'active')
            ->where('device_id', '!=', $deviceId)
            ->count();

        if ($activeDevices >= self::MAX_DEVICES) {
            return $this->denied(GraceStateMachine::STATE_ACTIVE_ONLINE, 'Device limit reached.');
        }

        $device = LicenseDevice::query()->updateOrCreate(
            [
                'license_id' => $license->id,
                'device_id' => $deviceId,
            ],
            [
                'platform' => $platform,
                'app_version' => $appVersion,
                'first_activated_at' => now(),
                'last_validated_at' => now(),
                'status' => 'active',
            ]
        );

        LicenseGrace::query()
            ->where('license_id', $license->id)
            ->where('device_id', $deviceId)
            ->whereNull('cleared_at')
            ->update(['cleared_at' => now()]);

        return $this->allowed($license, $device, 'License activated successfully.');
    }

    public function validate(string $licenseKey, string $deviceId, string $platform, string $appVersion, bool $graceUsed = false, mixed $graceStartedAt = null): array
    {
        $license = $this->findActiveLicense($licenseKey);

        if (! $license) {
            return $this->denied(GraceStateMachine::STATE_NO_RECORD, 'License not found or inactive.');
        }

        $device = LicenseDevice::query()
            ->where('license_id', $license->id)
            ->where('device_id', $deviceId)
            ->where('status', 'active')
            ->first();

        if (! $device) {
            return $this->denied(GraceStateMachine::STATE_NO_RECORD, 'Device must activate first.');
        }

        $device->forceFill([
            'platform' => $platform,
            'app_version' => $appVersion,
            'last_validated_at' => now(),
        ])->save();

        LicenseGrace::query()
            ->where('license_id', $license->id)
            ->where('device_id', $deviceId)
            ->whereNull('cleared_at')
            ->update(['cleared_at' => now()]);

        return $this->allowed($license, $device, 'License validated.');
    }

    public function deactivate(string $licenseKey, string $deviceId): void
    {
        $license = License::query()
            ->whereRaw('LOWER(license_key) = ?', [strtolower($licenseKey)])
            ->first();

        if (! $license) {
            return;
        }

        LicenseDevice::query()
            ->where('license_id', $license->id)
            ->where('device_id', $deviceId)
            ->update(['status' => 'deactivated']);
    }

    private function findActiveLicense(string $licenseKey, ?User $user = null): ?License
    {
        return License::query()
            ->whereRaw('LOWER(license_key) = ?', [strtolower($licenseKey)])
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->when($user, fn ($query) => $query->where('user_id', $user->id))
            ->first();
    }

    private function allowed(License $license, LicenseDevice $device, string $message): array
    {
        $activeDevices = LicenseDevice::query()
            ->where('license_id', $license->id)
            ->where('status', 'active')
            ->count();

        return [
            'allowed' => true,
            'result' => GraceStateMachine::STATE_ACTIVE_ONLINE,
            'message' => $message,
            'license_key' => $license->license_key,
            'license_kind' => $license->kind ?? 'paid',
            'expires_at' => $license->expires_at?->toIso8601String(),
            'device_id' => $device->device_id,
            'max_devices' => self::MAX_DEVICES,
            'active_devices' => $activeDevices,
        ];
    }

    private function denied(string $result, string $message): array
    {
        return [
            'allowed' => false,
            'result' => $result,
            'message' => $message,
            'max_devices' => self::MAX_DEVICES,
        ];
    }
}
