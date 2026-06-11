<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\LicenseService;

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

}
