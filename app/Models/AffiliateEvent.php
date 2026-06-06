<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateEvent extends Model
{
    protected $fillable = ['affiliate_id', 'type', 'order_id', 'revenue_cents', 'ip_hash'];

    protected $casts = [
        'revenue_cents' => 'integer',
    ];
}

