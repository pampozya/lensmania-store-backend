<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteEvent extends Model
{
    protected $fillable = [
        'visitor_hash',
        'ip_hash',
        'name',
        'path',
        'referrer',
        'product_slug',
        'promo_code',
        'affiliate',
        'value',
        'currency',
        'country',
        'city',
        'device_type',
        'browser',
        'os',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'value' => 'float',
    ];
}
