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
        'price_hushcut',
        'price_babelcut',
        'price_bundle',
        'link_hushcut',
        'link_babelcut',
        'link_bundle',
        'active',
        'expires_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime',
        'discount_percent' => 'integer',
        'price_hushcut' => 'decimal:2',
        'price_babelcut' => 'decimal:2',
        'price_bundle' => 'decimal:2',
    ];

    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = strtoupper((string) preg_replace('/\s+/', '', (string) $value));
    }
}
