<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateVisit extends Model
{
    protected $fillable = ['affiliate_id', 'ip_hash', 'user_agent', 'referrer', 'landing_path'];

    public $timestamps = true;
}
