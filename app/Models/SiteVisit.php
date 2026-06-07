<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    protected $fillable = [
        'visitor_hash',
        'ip_hash',
        'path',
        'landing_path',
        'referrer',
        'promo_code',
        'affiliate',
        'country',
        'region',
        'city',
        'device_type',
        'browser',
        'os',
        'screen',
        'language',
        'timezone',
        'user_agent',
    ];
}
