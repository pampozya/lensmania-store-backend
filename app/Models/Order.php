<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'product_slug',
        'amount_usd',
        'quote_id',
        'amount_cents',
        'currency',
        'paypal_order_id',
        'paypal_capture_id',
        'paypal_payment_id',
        'status',
        'api_status',
        'promo_code_id',
        'promo_code',
        'license_key',
        'download_url',
        'selection_metadata',
        'affiliate_id',
        'purchased_at',
        'paid_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'amount_usd' => 'float',
        'paid_at' => 'datetime',
        'purchased_at' => 'datetime',
        'selection_metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getProductSlugAttribute($value): ?string
    {
        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        if ($this->product) {
            return $this->product->slug;
        }

        return null;
    }

    public function getProductNameAttribute(): string
    {
        if ($this->product) {
            return (string) $this->product->name;
        }

        $slug = (string) $this->product_slug;
        return $slug !== '' ? ucfirst($slug) : 'Unknown';
    }

    public function getAmountUsdAttribute($value): ?float
    {
        if ($value !== null) {
            return (float) $value;
        }

        return $this->amount_cents === null ? null : $this->amount_cents / 100;
    }

    public function getDerivedStatusAttribute(): string
    {
        if ($this->api_status === 'fulfilled') {
            return 'fulfilled';
        }

        if ($this->api_status === 'pending') {
            return 'pending';
        }

        if ($this->status === 'paid') {
            return 'paid';
        }

        return 'pending';
    }
}
