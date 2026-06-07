<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private JwtService $jwtService)
    {
    }

    public function signup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Email already exists',
            'password.min' => 'Password too short',
        ]);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
        ]);

        return response()->json([
            'token' => $this->jwtService->issue($user),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', strtolower(trim($data['email'])))->first();

        if (! $user || ! Hash::check($data['password'], (string) $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'token' => $this->jwtService->issue($user),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
        ], 200);
    }

    public function google(Request $request): JsonResponse
    {
        $data = $request->validate([
            'credential' => ['required', 'string'],
        ]);

        $clientId = (string) config('services.google.client_id', '');
        if ($clientId === '') {
            return response()->json(['error' => 'Google login is not configured'], 500);
        }

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $data['credential'],
        ]);

        if (! $response->ok()) {
            return response()->json(['error' => 'Google sign-in failed'], 401);
        }

        $payload = $response->json();
        if (($payload['aud'] ?? '') !== $clientId) {
            return response()->json(['error' => 'Google sign-in failed'], 401);
        }

        $emailVerified = $payload['email_verified'] ?? false;
        if (! ($emailVerified === true || $emailVerified === 'true' || $emailVerified === '1')) {
            return response()->json(['error' => 'Google email is not verified'], 401);
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '') {
            return response()->json(['error' => 'Google account email missing'], 401);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $name = $email;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Str::random(32),
                'email_verified_at' => now(),
            ]);
        }

        return response()->json([
            'token' => $this->jwtService->issue($user),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Logged out'], 200);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ], 200);
    }

    public function orders(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Load all user licenses keyed by product_id for quick lookup
        $licenses = \App\Models\License::where('user_id', $user->id)
            ->get()
            ->keyBy('product_id');

        $rows = Order::query()
            ->with('product')
            ->where('user_id', $user->id)
            ->latest('purchased_at')
            ->latest('created_at')
            ->get()
            ->map(fn (Order $order) => $this->formatOrder($order, $licenses));

        return response()->json($rows, 200);
    }

    /**
     * Resend the license + download email for an order the authenticated user owns.
     * Also (idempotently) ensures licenses exist, so it doubles as a self-service repair.
     */
    public function resendEmail(Request $request, int $order): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $orderModel = Order::where('id', $order)
            ->where('user_id', $user->id)
            ->first();

        if (! $orderModel) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        try {
            // Ensure licenses exist (idempotent) then send the email.
            app(\App\Services\FulfillmentService::class)->fulfillStaticOrder($orderModel);
            return response()->json(['ok' => true, 'message' => 'Email sent to ' . $user->email]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => 'Could not send email'], 500);
        }
    }

    private function formatOrder(Order $order, \Illuminate\Support\Collection $licenses): array
    {
        // For bundles, expand into one card per license
        $product = $order->product;
        $isBundleOrder = $product?->is_bundle || $order->product_slug === 'bundle';

        if ($isBundleOrder) {
            $items = \App\Models\BundleItem::where('bundle_product_id', $product->id)
                ->with('itemProduct')
                ->get();

            $licenseCards = $items->map(function ($item) use ($licenses, $order) {
                $license = $licenses->get($item->item_product_id);
                $slug = $item->itemProduct?->slug ?? 'unknown';
                return [
                    'product_slug' => $slug,
                    'product_name' => $item->itemProduct?->name ?? 'Unknown',
                    'license_key' => $license?->license_key,
                    'license_status' => $license?->status ?? 'pending',
                    'download_url' => $license ? $this->resolveDownloadUrl($slug, $order) : null,
                ];
            })->values()->all();
        } else {
            $license = $licenses->get($product?->id);
            $licenseCards = [[
                'product_slug' => $order->product_slug,
                'product_name' => $order->product_name,
                'license_key' => $license?->license_key,
                'license_status' => $license?->status ?? 'pending',
                'download_url' => $license ? $this->resolveDownloadUrl($order->product_slug, $order) : null,
            ]];
        }

        return [
            'id' => $order->id,
            'product_slug' => $order->product_slug,
            'product_name' => $order->product_name,
            'amount_usd' => $order->amount_usd,
            'promo_code' => $order->promo_code,
            'status' => $order->derived_status,
            'is_bundle' => $isBundleOrder,
            'licenses' => $licenseCards,
            'selection_metadata' => $order->selection_metadata,
            'purchased_at' => optional($order->purchased_at)->toIso8601String(),
        ];
    }

    /**
     * Resolve the installer download URL for a product, honouring the platform/app
     * the customer chose at checkout (stored in the order's selection_metadata).
     * Falls back to the product's default URL when no specific variant matches.
     */
    private function resolveDownloadUrl(string $slug, Order $order): ?string
    {
        $config = config("downloads.products.{$slug}");
        if (! $config) {
            return null;
        }

        // Figure out the selected platform + app for this product from the order metadata.
        $meta = $order->selection_metadata ?? [];
        $sel = $meta[$slug] // bundle: per-product selection (e.g. metadata['hushcut'])
            ?? $meta['product_version'] // single-product order
            ?? $meta; // flat selection

        $platform = $sel['platform'] ?? 'mac';
        $app = $sel['app'] ?? 'premiere';

        // Prefer the exact variant; fall back to the product default URL.
        return $config['variants'][$platform][$app]
            ?? $config['url']
            ?? null;
    }
}
