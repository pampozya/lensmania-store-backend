<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\LicenseDevice;
use Illuminate\Console\Command;

class ResetLicenseDevices extends Command
{
    /**
     * Space-separated args only: Render one-off jobs exec the command
     * without a shell, so quoted/piped invocations do not work.
     */
    protected $signature = 'license:reset-devices {license_key}';

    protected $description = 'Delete all device activations for a license key, freeing its device slots';

    public function handle(): int
    {
        $key = (string) $this->argument('license_key');

        $licenseIds = License::query()->where('license_key', $key)->pluck('id');

        if ($licenseIds->isEmpty()) {
            $this->error("No license found for key: {$key}");

            return self::FAILURE;
        }

        $deleted = LicenseDevice::query()->whereIn('license_id', $licenseIds)->delete();

        $this->info("Deleted {$deleted} device activation(s) for license key {$key}.");

        return self::SUCCESS;
    }
}
