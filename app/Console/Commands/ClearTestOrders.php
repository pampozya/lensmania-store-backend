<?php

namespace App\Console\Commands;

use App\Models\Entitlement;
use App\Models\Order;
use App\Models\PromoRedemption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remove test ORDERS only (the launch-testing revenue), keeping everything else.
 *
 *  - Deletes ALL orders so Total Revenue / Fulfilled orders read $0 / 0.
 *  - DB foreign keys then cascade-delete the entitlements and promo_redemptions
 *    that belonged to those orders; support_notes / email_delivery_logs /
 *    affiliate_events keep their rows with order_id set to NULL.
 *  - KEEPS all customer (user) accounts.
 *  - KEEPS all trials.
 *  - KEEPS all licenses.
 *  - KEEPS all analytics (site visits/events) and storefront promos.
 *
 * Usage:  php artisan store:clear-test-orders            (dry run, shows what would go)
 *         php artisan store:clear-test-orders --force     (actually deletes)
 */
class ClearTestOrders extends Command
{
    protected $signature = 'store:clear-test-orders {--force : Actually delete (otherwise dry-run)}';

    protected $description = 'Delete all test orders so revenue reads $0; keep users, trials, licenses and analytics';

    public function handle(): int
    {
        $dry = ! $this->option('force');
        $this->info($dry ? '🔍 DRY RUN (no changes). Re-run with --force to apply.' : '⚠️  FORCE: deleting test orders…');

        $orderCount = Order::count();
        $fulfilledRevenue = Order::query()
            ->where('api_status', 'fulfilled')
            ->get(['amount_usd', 'amount_cents'])
            ->sum(fn ($o) => $o->amount_usd !== null
                ? (float) $o->amount_usd
                : (($o->amount_cents ?? 0) / 100));

        $this->line('');
        $this->line('Orders to DELETE (ALL): ' . $orderCount);
        $this->line('Fulfilled revenue being removed: $' . number_format($fulfilledRevenue, 2));
        $this->line('Cascade-deleted with them:');
        $this->line('   - entitlements: ' . Entitlement::count());
        $this->line('   - promo_redemptions: ' . PromoRedemption::count());
        $this->line('');
        $this->line('KEPT (untouched): users/customers, trials, licenses, site analytics, storefront promos.');

        if ($dry) {
            $this->info('Dry run complete — nothing deleted.');
            return self::SUCCESS;
        }

        DB::transaction(function () {
            // Mass delete fires DB-level FK cascades for entitlements + promo_redemptions
            // and nulls order_id on support_notes / email_delivery_logs / affiliate_events.
            Order::query()->delete();
        });

        $this->info('✅ All orders deleted. Total Revenue / Fulfilled orders now read $0 / 0. Customers, trials, licenses and analytics kept.');

        return self::SUCCESS;
    }
}
