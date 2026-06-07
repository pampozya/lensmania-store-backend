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
