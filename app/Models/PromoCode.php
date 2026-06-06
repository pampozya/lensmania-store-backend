<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'product_id',
        'discount_type',
        'discount_value',
        'max_uses',
        'uses_count',
        'per_user_limit',
        'min_price_cents',
        'starts_at',
        'expires_at',
        'active',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'per_user_limit' => 'integer',
        'min_price_cents' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'active' => 'boolean',
    ];
}
