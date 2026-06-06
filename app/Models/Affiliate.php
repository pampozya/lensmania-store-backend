<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    protected $fillable = ['user_id', 'code', 'label', 'status', 'hold_days', 'min_payout_cents'];

    protected $casts = [
        'hold_days' => 'integer',
        'min_payout_cents' => 'integer',
    ];
}
