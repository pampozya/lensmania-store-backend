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

        $rows = Order::query()
            ->where('user_id', $user->id)
            ->latest('purchased_at')
            ->latest('created_at')
            ->get()
            ->map(fn (Order $order) => $this->formatOrder($order));

        return response()->json($rows, 200);
    }

    private function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'product_slug' => $order->product_slug,
            'product_name' => $order->product_name,
            'amount_usd' => $order->amount_usd,
            'promo_code' => $order->promo_code,
            'status' => $order->derived_status,
            'license_key' => $order->license_key,
            'download_url' => $order->download_url,
            'selection_metadata' => $order->selection_metadata,
            'purchased_at' => optional($order->purchased_at)->toIso8601String(),
        ];
    }
}
