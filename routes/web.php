<?php

use App\Http\Controllers\Checkout\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('/favicon.ico', function () {
    $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <rect width="64" height="64" rx="14" fill="#050505"/>
  <circle cx="32" cy="32" r="18" fill="none" stroke="#d4af37" stroke-width="6"/>
  <circle cx="32" cy="32" r="7" fill="#d4af37"/>
</svg>
SVG;

    return response($svg, 200, [
        'Content-Type' => 'image/svg+xml',
        'Cache-Control' => 'public, max-age=604800',
    ]);
});

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/checkout/paypal/create', [CheckoutController::class, 'createPayPalOrder']);
});

Route::get('/checkout/return', [CheckoutController::class, 'return']);
Route::get('/checkout/cancel', [CheckoutController::class, 'cancel']);

Route::get('/checkout/thank-you', function () {
    return view('customer.thank-you');
})->name('checkout.thank-you');
