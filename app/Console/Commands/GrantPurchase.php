<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Manually grant a paid purchase + licenses to a user, as if they paid.
 *
 * Usage:
 *   php artisan grant:purchase someone@example.com cinecut --platform=mac-arm64 --app=premiere
 */
class GrantPurchase extends Command
{
    protected $signature = 'grant:purchase
        {email : The user account email}
        {product : Product slug (cinecut)}
        {--platform=mac-arm64 : Platform}
        {--app=premiere : Application}';

    protected $description = 'Grant a paid order + licenses to a user without payment (manual fulfillment)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $slug = $this->argument('product');
        $platform = $this->option('platform');
        $app = $this->option('app');

        if ($slug !== 'cinecut') {
            $this->error("Invalid product slug: {$slug} (use cinecut)");
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("User not found: {$email}");
            return self::FAILURE;
        }

        $product = Product::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => 'CineCut',
                'price_cents' => 3500,
                'is_bundle' => false,
                'active' => true,
            ]
        );

        $this->info("User: {$user->email} (id={$user->id})");
        $this->info("Product: {$product->name} (slug={$product->slug}, id={$product->id})");

        DB::transaction(function () use ($user, $product, $slug, $platform, $app) {
            $order = Order::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'product_slug' => $product->slug,
                'amount_cents' => $product->price_cents,
                'amount_usd' => number_format($product->price_cents / 100, 2, '.', ''),
                'currency' => 'USD',
                'status' => 'paid',
                'api_status' => 'manual_grant',
                'promo_code' => null,
                'selection_metadata' => [
                    'product_version' => [
                        'product' => $slug,
                        'platform' => $platform,
                        'app' => $app,
                        'label' => ucfirst($platform) . ' ' . ($app === 'resolve' ? 'DaVinci Resolve' : 'Premiere Pro'),
                    ],
                    'granted_manually' => true,
                ],
                'paid_at' => now(),
                'purchased_at' => now(),
            ]);

            $this->info("Order created: id={$order->id}, status=paid");

            $productIds = [$product->id];

            foreach ($productIds as $pid) {
                $existing = License::where('user_id', $user->id)
                    ->where('product_id', $pid)
                    ->where('kind', 'paid')
                    ->first();

                if ($existing) {
                    $this->line("  License already exists for product_id={$pid}: {$existing->license_key}");
                    continue;
                }

                $license = License::create([
                    'user_id' => $user->id,
                    'product_id' => $pid,
                    'license_key' => $this->buildLicenseKey($user->id, $pid, $order->id),
                    'kind' => 'paid',
                    'status' => 'active',
                    'expires_at' => null,
                ]);

                $pName = Product::find($pid)?->name ?? "product {$pid}";
                $this->info("  License created for {$pName}: {$license->license_key}");
            }
        });

        $this->newLine();
        $this->info("✅ Done. {$user->email} now owns {$product->name} ({$platform}/{$app}).");

        return self::SUCCESS;
    }

    private function buildLicenseKey(int $userId, int $productId, int $orderId): string
    {
        $productSlug = Product::query()->whereKey($productId)->value('slug') ?: 'cinecut';

        do {
            $licenseKey = LicenseService::generate((string) $productSlug);
        } while (License::query()->where('license_key', $licenseKey)->exists());

        return $licenseKey;
    }
}
