<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontPromo extends Model
{
    protected $fillable = [
        'code',
        'label',
        'affiliate',
        'discount_percent',
        'price_cinecut',
        'link_cinecut',
        'active',
        'expires_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime',
        'discount_percent' => 'integer',
        'price_cinecut' => 'decimal:2',
    ];

    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = strtoupper((string) preg_replace('/\s+/', '', (string) $value));
    }
}
