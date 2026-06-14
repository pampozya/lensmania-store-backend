<?php

namespace App\Services;

use App\Models\Entitlement;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LicenseIssuerService
{
    public function __construct(private TrialService $trialService)
    {
    }

    public function issue(array $payload): array
    {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $kind = $this->normalizeKind((string) ($payload['kind'] ?? ''));
        $productSlug = strtolower(trim((string) ($payload['product'] ?? 'cinecut')));
        $platform = trim((string) ($payload['platform'] ?? 'mac-arm64'));
        $app = trim((string) ($payload['app'] ?? 'premiere'));
        $createUser = (bool) ($payload['create_user'] ?? false);
        $name = trim((string) ($payload['name'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email address.',
            ]);
        }

        if (! in_array($kind, ['trial', 'paid'], true)) {
            throw ValidationException::withMessages([
                'kind' => 'Invalid license kind. Use trial, full, or paid.',
            ]);
        }

        if ($productSlug !== 'cinecut') {
            throw ValidationException::withMessages([
                'product' => 'Invalid product. Use cinecut.',
            ]);
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user && ! $createUser) {
            throw ValidationException::withMessages([
                'email' => 'User not found. Enable create user to create it first.',
            ]);
        }

        if (! $user) {
            $user = User::create([
                'name' => $name !== '' ? $name : $email,
                'email' => $email,
                'password' => Str::random(40),
                'email_verified_at' => now(),
                'is_admin' => false,
            ]);
        }

        if ($kind === 'trial' && $this->hasPaidAccess($user)) {
            throw ValidationException::withMessages([
                'kind' => 'User already has paid access; not issuing a trial license.',
            ]);
        }

        return $kind === 'trial'
            ? $this->issueTrial($user, $platform, $app)
            : $this->issuePaid($user, $productSlug, $platform, $app);
    }

    private function issueTrial(User $user, string $platform, string $app): array
    {
        try {
            $status = $this->trialService->start($user, [
                'platform' => $platform,
                'app_version' => $app,
            ]);
            $created = true;
        } catch (ValidationException $exception) {
            $status = $this->trialService->status($user);
            if (($status['status'] ?? 'not_started') === 'not_started') {
                throw $exception;
            }
            $created = false;
        }

        return [
            'ok' => true,
            'created' => $created,
            'email' => $user->email,
            'user_id' => $user->id,
            'kind' => 'trial',
            'license_key' => $status['license_key'] ?? null,
            'expires_at' => $status['expires_at'] ?? null,
            'status' => $status['status'] ?? null,
            'paid_access' => $status['paid_access'] ?? false,
        ];
    }

    private function issuePaid(User $user, string $productSlug, string $platform, string $app): array
    {
        return DB::transaction(function () use ($user, $productSlug, $platform, $app): array {
            $product = Product::query()->firstOrCreate(
                ['slug' => $productSlug],
                [
                    'name' => 'CineCut',
                    'price_cents' => 3500,
                    'is_bundle' => false,
                    'active' => true,
                ],
            );

            $existing = License::query()
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->where('kind', 'paid')
                ->first();

            if ($existing) {
                return [
                    'ok' => true,
                    'created' => false,
                    'email' => $user->email,
                    'user_id' => $user->id,
                    'kind' => 'paid',
                    'license_key' => $existing->license_key,
                    'expires_at' => $existing->expires_at?->toIso8601String(),
                    'status' => $existing->status,
                    'order_id' => null,
                ];
            }

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
                        'product' => $productSlug,
                        'platform' => $platform,
                        'app' => $app,
                        'label' => $this->labelForVersion($platform, $app),
                    ],
                    'granted_manually' => true,
                    'issued_by' => 'admin-license-issuer',
                ],
                'paid_at' => now(),
                'purchased_at' => now(),
            ]);

            Entitlement::firstOrCreate([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'order_id' => $order->id,
            ], [
                'active' => true,
            ]);

            $license = License::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'license_key' => $this->uniquePaidLicenseKey($product->slug),
                'kind' => 'paid',
                'status' => 'active',
                'expires_at' => null,
            ]);

            $this->trialService->markConverted($user);

            return [
                'ok' => true,
                'created' => true,
                'email' => $user->email,
                'user_id' => $user->id,
                'kind' => 'paid',
                'license_key' => $license->license_key,
                'expires_at' => null,
                'status' => $license->status,
                'order_id' => $order->id,
            ];
        });
    }

    private function uniquePaidLicenseKey(string $productSlug): string
    {
        do {
            $licenseKey = LicenseService::generate($productSlug);
        } while (License::query()->where('license_key', $licenseKey)->exists());

        return $licenseKey;
    }

    private function hasPaidAccess(User $user): bool
    {
        return Entitlement::query()
            ->where('user_id', $user->id)
            ->where('active', true)
            ->exists();
    }

    private function normalizeKind(string $kind): string
    {
        $kind = strtolower(trim($kind));

        return $kind === 'full' ? 'paid' : $kind;
    }

    private function labelForVersion(string $platform, string $app): string
    {
        return ucfirst($platform) . ' ' . ($app === 'resolve' ? 'DaVinci Resolve' : 'Premiere Pro');
    }
}
