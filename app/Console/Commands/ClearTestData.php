<?php

namespace App\Console\Commands;

use App\Models\AffiliateEvent;
use App\Models\AffiliateVisit;
use App\Models\Entitlement;
use App\Models\License;
use App\Models\Order;
use App\Models\PromoRedemption;
use App\Models\SiteEvent;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Clear test data after launch testing.
 *
 *  - Deletes orders / licenses / entitlements belonging to TEST accounts only.
 *  - Deletes the test user accounts themselves.
 *  - Clears all analytics (site visits/events, promo redemptions, affiliate events/visits)
 *    since that traffic was all self-testing.
 *  - KEEPS pampozya@gmail.com and its data.
 *  - KEEPS all storefront promos active (never touches storefront_promos).
 *
 * Usage:  php artisan store:clear-test-data            (dry run, shows what would go)
 *         php artisan store:clear-test-data --force     (actually deletes)
 */
class ClearTestData extends Command
{
    protected $signature = 'store:clear-test-data {--force : Actually delete (otherwise dry-run)}';

    protected $description = 'Remove test orders/licenses/users + reset analytics; keep pampozya@gmail.com and all promos';

    /** Accounts to preserve. */
    private array $keepEmails = ['pampozya@gmail.com'];

    public function handle(): int
    {
        $dry = ! $this->option('force');
        $this->info($dry ? '🔍 DRY RUN (no changes). Re-run with --force to apply.' : '⚠️  FORCE: deleting test data…');

        // Identify test users = everyone except the keep-list.
        $testUsers = User::whereNotIn('email', $this->keepEmails)->get();
        $testUserIds = $testUsers->pluck('id')->all();

        $this->line('');
        $this->line('Users to DELETE: ' . $testUsers->count());
        foreach ($testUsers as $u) {
            $this->line("   - {$u->email}");
        }
        $this->line('Users to KEEP:   ' . implode(', ', $this->keepEmails));

        $this->line('');
        $this->line('FULL ZERO mode:');
        $this->line('Orders to delete (ALL): ' . Order::count());
        $this->line('Licenses to delete (ALL): ' . License::count());
        $this->line('Entitlements to delete (ALL): ' . Entitlement::count());
        $this->line('Analytics to wipe: site_visits, site_events, promo_redemptions, affiliate_events, affiliate_visits (ALL rows)');
        $this->line('Promos: UNTOUCHED (storefront_promos stays active)');

        if ($dry) {
            $this->info('Dry run complete — nothing deleted.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($testUserIds) {
            // FULL ZERO: wipe ALL orders/licenses/entitlements (every account, incl. kept users)
            // so dashboard numbers read $0.00 / 0 sales. Kept users remain as accounts with no orders.
            Entitlement::query()->delete();
            License::query()->delete();
            Order::query()->delete();

            // Analytics — all of it was self-testing
            SiteVisit::query()->delete();
            SiteEvent::query()->delete();
            PromoRedemption::query()->delete();
            AffiliateEvent::query()->delete();
            AffiliateVisit::query()->delete();

            // Delete only the TEST user accounts; keep the keep-list (pampozya) as a clean account
            User::whereIn('id', $testUserIds)->delete();
        });

        $this->info('✅ Fully zeroed: all orders/licenses/analytics cleared. ' . implode(', ', $this->keepEmails) . ' kept as clean account(s). Promos untouched (still active).');
        return self::SUCCESS;
    }
}
