<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseGrace extends Model
{
    protected $table = 'license_grace';

    protected $fillable = [
        'license_id',
        'device_id',
        'used_at',
        'cleared_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'cleared_at' => 'datetime',
    ];
}
