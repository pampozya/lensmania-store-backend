<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'price_cents', 'is_bundle', 'active'];

    protected $casts = [
        'price_cents' => 'integer',
        'is_bundle' => 'boolean',
        'active' => 'boolean',
    ];
}
