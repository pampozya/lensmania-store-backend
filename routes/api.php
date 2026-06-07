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

// TEMP one-time repair: ensure bundle_items link the bundle to HushCut+BabelCut.
// Reports the resulting state. Remove after running. Usage: /api/_fixbundle?key=lm-diag-2026
Route::get('/_fixbundle', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'lm-diag-2026') {
        return response()->json(['error' => 'forbidden'], 403);
    }

    $hushcut  = \App\Models\Product::firstOrCreate(['slug' => 'hushcut'], ['name' => 'HushCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true]);
    $babelcut = \App\Models\Product::firstOrCreate(['slug' => 'babelcut'], ['name' => 'BabelCut', 'price_cents' => 3500, 'is_bundle' => false, 'active' => true]);
    $bundle   = \App\Models\Product::firstOrCreate(['slug' => 'bundle'], ['name' => 'Studio Pass', 'price_cents' => 5000, 'is_bundle' => true, 'active' => true]);

    // Ensure bundle is flagged as a bundle (in case it was created without it)
    if (! $bundle->is_bundle) {
        $bundle->update(['is_bundle' => true]);
    }

    \App\Models\BundleItem::firstOrCreate(['bundle_product_id' => $bundle->id, 'item_product_id' => $hushcut->id]);
    \App\Models\BundleItem::firstOrCreate(['bundle_product_id' => $bundle->id, 'item_product_id' => $babelcut->id]);

    $items = \App\Models\BundleItem::where('bundle_product_id', $bundle->id)
        ->with('itemProduct')
        ->get()
        ->map(fn ($i) => $i->itemProduct?->slug)
        ->all();

    return response()->json([
        'ok' => true,
        'bundle_id' => $bundle->id,
        'bundle_is_bundle' => (bool) $bundle->is_bundle,
        'hushcut_id' => $hushcut->id,
        'babelcut_id' => $babelcut->id,
        'bundle_items' => $items,
    ]);
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
