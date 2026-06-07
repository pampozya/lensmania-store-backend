<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailDeliveryLog extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'email',
        'type',
        'subject',
        'provider',
        'provider_message_id',
        'status',
        'error',
        'sent_at',
        'delivered_at',
        'opened_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
