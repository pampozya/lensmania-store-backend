<?php

namespace App\Console\Commands;

use App\Models\Entitlement;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\TrialService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssueLicense extends Command
{
    protected $signature = 'license:issue
        {email : Customer email}
        {kind : License kind: trial, full, or paid}
        {--create-user : Create the customer account if it does not exist}
        {--name= : Customer name when creating a new account}
        {--product=cinecut : Product slug}
        {--platform=mac-arm64 : Platform metadata}
        {--app=premiere : App metadata}
        {--json : Output machine-readable JSON}';

    protected $description = 'Issue a CineCut trial or full paid license to a customer';

    public function __construct(private TrialService $trialService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $kind = $this->normalizeKind((string) $this->argument('kind'));
        $productSlug = strtolower(trim((string) $this->option('product')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failWith('Invalid email address.');
        }

        if (! in_array($kind, ['trial', 'paid'], true)) {
            return $this->failWith('Invalid license kind. Use trial, full, or paid.');
        }

        if ($productSlug !== 'cinecut') {
            return $this->failWith('Invalid product. Use cinecut.');
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user && ! $this->option('create-user')) {
            return $this->failWith('User not found. Re-run with --create-user to create it.');
        }

        if (! $user) {
            $user = User::create([
                'name' => $this->option('name') ?: $email,
                'email' => $email,
                'password' => Str::random(40),
                'email_verified_at' => now(),
                'is_admin' => false,
            ]);
        }

        if ($kind === 'trial' && $this->hasPaidAccess($user)) {
            return $this->failWith('User already has paid access; not issuing a trial license.');
        }

        try {
            $result = $kind === 'trial'
                ? $this->issueTrial($user)
                : $this->issuePaid($user, $productSlug);
        } catch (ValidationException $exception) {
            return $this->failWith($this->firstValidationMessage($exception));
        }

        $this->printResult($result);

        return self::SUCCESS;
    }

    private function issueTrial(User $user): array
    {
        try {
            $status = $this->trialService->start($user, [
                'platform' => $this->option('platform'),
                'app_version' => $this->option('app'),
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

    private function issuePaid(User $user, string $productSlug): array
    {
        return DB::transaction(function () use ($user, $productSlug): array {
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
                        'platform' => $this->option('platform'),
                        'app' => $this->option('app'),
                        'label' => $this->labelForVersion(),
                    ],
                    'granted_manually' => true,
                    'issued_by' => 'license:issue',
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

    private function labelForVersion(): string
    {
        $app = (string) $this->option('app');
        $platform = (string) $this->option('platform');

        return ucfirst($platform) . ' ' . ($app === 'resolve' ? 'DaVinci Resolve' : 'Premiere Pro');
    }

    private function printResult(array $result): void
    {
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_SLASHES));
            return;
        }

        $this->info(($result['created'] ? 'License issued' : 'Existing license found') . ': ' . $result['license_key']);
        $this->line('Email: ' . $result['email']);
        $this->line('Kind: ' . $result['kind']);
        $this->line('Status: ' . $result['status']);
        $this->line('Expires: ' . ($result['expires_at'] ?? 'never'));
        if (array_key_exists('order_id', $result) && $result['order_id']) {
            $this->line('Order ID: ' . $result['order_id']);
        }
    }

    private function failWith(string $message): int
    {
        if ($this->option('json')) {
            $this->line(json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_SLASHES));
        } else {
            $this->error($message);
        }

        return self::FAILURE;
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            if (is_array($messages) && isset($messages[0])) {
                return (string) $messages[0];
            }
        }

        return $exception->getMessage();
    }
}
