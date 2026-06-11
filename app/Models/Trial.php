<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trial extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'status',
        'started_at',
        'expires_at',
        'jobs_used',
        'jobs_limit',
        'minutes_used',
        'minutes_limit',
        'device_id',
        'device_label',
        'platform',
        'app_version',
        'limit_reached_at',
        'converted_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'limit_reached_at' => 'datetime',
        'converted_at' => 'datetime',
        'jobs_used' => 'integer',
        'jobs_limit' => 'integer',
        'minutes_used' => 'integer',
        'minutes_limit' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
