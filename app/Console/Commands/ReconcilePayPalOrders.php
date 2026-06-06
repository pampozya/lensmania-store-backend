<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReconcilePayPalOrders extends Command
{
    protected $signature = 'orders:reconcile-paypal {--stale-minutes=20}';

    protected $description = 'Reconcile created PayPal orders against PayPal every 15 minutes.';

    public function handle(): int
    {
        $this->info('Reconcile stub executed.');
        return self::SUCCESS;
    }
}
