<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportNote extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'license_id',
        'admin_user_id',
        'category',
        'status',
        'body',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function license()
    {
        return $this->belongsTo(License::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
