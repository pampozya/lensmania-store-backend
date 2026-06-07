<?php

namespace App\Console\Commands;

use App\Models\BundleItem;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Manually grant a paid purchase + licenses to a user, as if they paid.
 *
 * Usage:
 *   php artisan grant:purchase pampozya@gmail.com bundle --platform=mac --app=premiere
 *   php artisan grant:purchase someone@example.com hushcut
 */
class GrantPurchase extends Command
{
    protected $signature = 'grant:purchase
        {email : The user account email}
        {product : Product slug (hushcut, babelcut, or bundle)}
        {--platform=mac : Platform (mac/windows)}
        {--app=premiere : Application (premiere/resolve)}';

    protected $description = 'Grant a paid order + licenses to a user without payment (manual fulfillment)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $slug = $this->argument('product');
        $platform = $this->option('platform');
        $app = $this->option('app');

        if (! in_array($slug, ['hushcut', 'babelcut', 'bundle'], true)) {
            $this->error("Invalid product slug: {$slug} (use hushcut, babelcut, or bundle)");
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
                'name' => match ($slug) {
                    'hushcut' => 'HushCut',
                    'babelcut' => 'BabelCut',
                    default => 'Studio Pass',
                },
                'price_cents' => $slug === 'bundle' ? 5000 : 3500,
                'is_bundle' => $slug === 'bundle',
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

            // Resolve products (bundle expands to its items).
            $productIds = $this->resolveProductIds($product->id);

            foreach ($productIds as $pid) {
                $existing = License::where('user_id', $user->id)
                    ->where('product_id', $pid)
                    ->first();

                if ($existing) {
                    $this->line("  License already exists for product_id={$pid}: {$existing->license_key}");
                    continue;
                }

                $license = License::create([
                    'user_id' => $user->id,
                    'product_id' => $pid,
                    'license_key' => $this->buildLicenseKey($user->id, $pid, $order->id),
                    'status' => 'active',
                ]);

                $pName = Product::find($pid)?->name ?? "product {$pid}";
                $this->info("  License created for {$pName}: {$license->license_key}");
            }
        });

        $this->newLine();
        $this->info("✅ Done. {$user->email} now owns {$product->name} ({$platform}/{$app}).");

        return self::SUCCESS;
    }

    private function resolveProductIds(int $productId): array
    {
        $product = Product::find($productId);
        if (! $product || ! $product->is_bundle) {
            return [$productId];
        }

        $items = BundleItem::where('bundle_product_id', $productId)
            ->pluck('item_product_id')
            ->all();

        return ! empty($items) ? $items : [$productId];
    }

    private function buildLicenseKey(int $userId, int $productId, int $orderId): string
    {
        return substr(hash('sha256', $userId . ':' . $productId . ':' . $orderId . ':' . config('app.key', 'fallback')), 0, 32);
    }
}
