<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    protected $fillable = ['user_id', 'code', 'label', 'status', 'hold_days', 'min_payout_cents', 'commission_bps'];

    protected $casts = [
        'hold_days' => 'integer',
        'min_payout_cents' => 'integer',
        'commission_bps' => 'integer',
    ];

    /**
     * Commission owed in cents for a given gross revenue (cents),
     * using this affiliate's commission rate (basis points). 1000 bps = 10%.
     */
    public function commissionOwedCents(int $revenueCents): int
    {
        return (int) round($revenueCents * ($this->commission_bps ?? 0) / 10000);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visits()
    {
        return $this->hasMany(AffiliateVisit::class);
    }

    public function events()
    {
        return $this->hasMany(AffiliateEvent::class);
    }
}
