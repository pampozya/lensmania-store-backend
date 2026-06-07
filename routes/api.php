<?php

use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\Download\DownloadController;
use App\Http\Controllers\Analytics\SiteVisitController;
use App\Http\Controllers\Analytics\SiteEventController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\License\LicenseController;
use App\Http\Controllers\PromoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->get('/health', function () {
    return response()->json(['ok' => true]);
});

Route::get('/promos', [PromoController::class, 'index']);

// TEMP diagnostic: send a test email and return the exact SMTP result.
// Remove after debugging. Usage: /api/_mailtest?to=pampozya@gmail.com&key=lm-diag-2026
Route::get('/_mailtest', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'lm-diag-2026') {
        return response()->json(['error' => 'forbidden'], 403);
    }
    $to = $request->query('to', 'pampozya@gmail.com');
    $config = [
        'mailer'   => config('mail.default'),
        'host'     => config('mail.mailers.smtp.host'),
        'port'     => config('mail.mailers.smtp.port'),
        'username' => config('mail.mailers.smtp.username'),
        'from'     => config('mail.from.address'),
        'from_name'=> config('mail.from.name'),
        'password_set' => ! empty(config('mail.mailers.smtp.password')),
    ];
    try {
        // If ?order=ID is passed, send the REAL OrderFulfilled license email for that order.
        if ($orderId = $request->query('order')) {
            $order = \App\Models\Order::with('user')->find($orderId);
            if (! $order) {
                return response()->json(['ok' => false, 'error' => 'Order not found', 'config' => $config], 404);
            }
            \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\OrderFulfilled($order));
            return response()->json(['ok' => true, 'sent_to' => $to, 'type' => 'OrderFulfilled', 'order_id' => $order->id, 'config' => $config]);
        }

        \Illuminate\Support\Facades\Mail::raw(
            'Lensmania Labs SMTP diagnostic — if you received this, email delivery works. Time: ' . now(),
            function ($m) use ($to) {
                $m->to($to)->subject('Lensmania SMTP test');
            }
        );
        return response()->json(['ok' => true, 'sent_to' => $to, 'config' => $config]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
            'config' => $config,
        ], 500);
    }
});

// TEMP diagnostic: create a PAID order for an email + fulfill it + send the license email.
// Simulates a full paid purchase. Remove after debugging.
// Usage: /api/_paidtest?email=info@lensmania.ae&product=bundle&key=lm-diag-2026
Route::get('/_paidtest', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'lm-diag-2026') {
        return response()->json(['error' => 'forbidden'], 403);
    }
    $email = $request->query('email', 'info@lensmania.ae');
    $slug  = $request->query('product', 'bundle');
    if (! in_array($slug, ['hushcut', 'babelcut', 'bundle'], true)) {
        return response()->json(['error' => 'bad product'], 422);
    }

    $user = \App\Models\User::where('email', $email)->first();
    if (! $user) {
        return response()->json(['error' => 'User not found: ' . $email], 404);
    }

    $product = \App\Models\Product::firstOrCreate(
        ['slug' => $slug],
        [
            'name' => match ($slug) { 'hushcut' => 'HushCut', 'babelcut' => 'BabelCut', default => 'Studio Pass' },
            'price_cents' => $slug === 'bundle' ? 5000 : 3500,
            'is_bundle' => $slug === 'bundle',
            'active' => true,
        ]
    );

    $order = \App\Models\Order::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'product_slug' => $slug,
        'amount_cents' => $product->price_cents,
        'amount_usd' => number_format($product->price_cents / 100, 2, '.', ''),
        'currency' => 'USD',
        'status' => 'created',
        'api_status' => 'pending',
        'promo_code' => 'DUMMYPAID',
        'paypal_payment_id' => 'TEST-' . uniqid(),
        'selection_metadata' => ['product_version' => ['product' => $slug, 'platform' => 'mac', 'app' => 'premiere']],
        'purchased_at' => now(),
    ]);

    try {
        app(\App\Services\FulfillmentService::class)->fulfillStaticOrder($order);
        return response()->json(['ok' => true, 'order_id' => $order->id, 'email' => $email, 'product' => $slug, 'note' => 'Paid order created, fulfilled, license email sent']);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'order_id' => $order->id, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/analytics/visit', [SiteVisitController::class, 'store'])
    ->middleware('throttle:120,1');

Route::post('/analytics/event', [SiteEventController::class, 'store'])
    ->middleware('throttle:240,1');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/checkout/quote', [CheckoutController::class, 'quote'])->name('checkout.quote')->middleware('throttle:60,1');
    Route::post('/checkout/fulfill-pending', [CheckoutController::class, 'fulfillPending'])->middleware('throttle:10,1');
    Route::get('/account/downloads/{build}', [DownloadController::class, 'download'])->name('download.by_build');
});

Route::post('/auth/signup', [AuthController::class, 'signup']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);

Route::middleware('api.jwt')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/orders', [AuthController::class, 'orders']);
    Route::post('/checkout/static-intent', [CheckoutController::class, 'staticIntent'])->middleware('throttle:60,1');
});

Route::post('/license/activate', [LicenseController::class, 'activate'])
    ->middleware(['throttle:30,1']);

Route::post('/license/validate', [LicenseController::class, 'validateLicense'])
    ->middleware(['throttle:60,1']);

Route::post('/license/deactivate', [LicenseController::class, 'deactivate'])
    ->middleware(['throttle:30,1']);

Route::post('/webhooks/paypal', [CheckoutController::class, 'paypalWebhook'])
    ->name('webhooks.paypal')->withoutMiddleware(['auth']);

Route::post('/payments/webhook', [PaymentWebhookController::class, 'webhook'])
    ->name('payments.webhook')
    ->withoutMiddleware(['auth']);

Route::get('/download/{token}', [DownloadController::class, 'stream'])
    ->name('download.token')
    ->middleware('throttle:120,1');
