<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_token',
        'user_id',
        'product_id',
        'base_cents',
        'discount_cents',
        'amount_cents',
        'currency',
        'promo_code_id',
        'affiliate_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'base_cents' => 'integer',
        'discount_cents' => 'integer',
        'amount_cents' => 'integer',
        'expires_at' => 'datetime',
    ];
}
