<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\FulfillmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FulfillOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(public Order $order) {}

    public function handle(FulfillmentService $service): void
    {
        $service->fulfillStaticOrder($this->order);
    }

    public function failed(\Throwable $e): void
    {
        report($e);
    }
}
