<?php

use App\Models\BundleItem;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time grant: give pampozya@gmail.com a paid Studio Pass (bundle) for
 * Mac/Premiere, with active HushCut + BabelCut licenses, as if they paid.
 * Safe to run on any environment — no-ops if the user doesn't exist or
 * already has the order/licenses.
 */
return new class extends Migration
{
    private string $email = 'pampozya@gmail.com';

    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('orders') || ! Schema::hasTable('licenses')) {
            return;
        }

        $user = User::where('email', $this->email)->first();
        if (! $user) {
            // User not registered yet — nothing to grant. (No error.)
            return;
        }

        $product = Product::firstOrCreate(
            ['slug' => 'bundle'],
            [
                'name' => 'Studio Pass',
                'price_cents' => 5000,
                'is_bundle' => true,
                'active' => true,
            ]
        );

        // Skip if a manual-grant bundle order already exists for this user.
        $already = Order::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('api_status', 'manual_grant')
            ->exists();

        if ($already) {
            return;
        }

        DB::transaction(function () use ($user, $product) {
            $order = Order::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'product_slug' => 'bundle',
                'amount_cents' => $product->price_cents,
                'amount_usd' => number_format($product->price_cents / 100, 2, '.', ''),
                'currency' => 'USD',
                'status' => 'paid',
                'api_status' => 'manual_grant',
                'selection_metadata' => [
                    'product_version' => [
                        'product' => 'bundle',
                        'platform' => 'mac',
                        'app' => 'premiere',
                        'label' => 'macOS Premiere Pro',
                    ],
                    'granted_manually' => true,
                ],
                'paid_at' => now(),
                'purchased_at' => now(),
            ]);

            // Bundle expands to its items (HushCut + BabelCut); fall back to bundle id.
            $productIds = BundleItem::where('bundle_product_id', $product->id)
                ->pluck('item_product_id')
                ->all();
            if (empty($productIds)) {
                $productIds = [$product->id];
            }

            foreach ($productIds as $pid) {
                $exists = License::where('user_id', $user->id)
                    ->where('product_id', $pid)
                    ->exists();
                if ($exists) {
                    continue;
                }

                License::create([
                    'user_id' => $user->id,
                    'product_id' => $pid,
                    'license_key' => substr(hash('sha256', $user->id . ':' . $pid . ':' . $order->id . ':' . config('app.key', 'fallback')), 0, 32),
                    'status' => 'active',
                ]);
            }
        });
    }

    public function down(): void
    {
        // Keep the granted order/licenses on rollback.
    }
};
