<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitlement extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}

