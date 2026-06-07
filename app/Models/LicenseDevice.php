<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseDevice extends Model
{
    protected $fillable = [
        'license_id',
        'device_id',
        'device_label',
        'platform',
        'app_version',
        'first_activated_at',
        'last_validated_at',
        'status',
    ];

    protected $casts = [
        'first_activated_at' => 'datetime',
        'last_validated_at' => 'datetime',
    ];

    public function license()
    {
        return $this->belongsTo(License::class);
    }
}
