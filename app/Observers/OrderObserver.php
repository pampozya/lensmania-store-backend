<?php

namespace App\Observers;

use App\Mail\OrderFulfilled;
use App\Models\Order;
use App\Services\LicenseService;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    public function updating(Order $order): void
    {
        if ($order->isDirty('api_status') && $order->api_status === 'fulfilled') {
            if (empty($order->license_key)) {
                $order->license_key = LicenseService::generate((string) $order->product_slug);
            }
        }
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged('api_status') && $order->api_status === 'fulfilled') {
            Mail::to($order->user->email)->send(new OrderFulfilled($order));
        }
    }
}
