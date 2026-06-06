<?php

use App\Http\Controllers\Checkout\CheckoutController;
use Illuminate\Support\Facades\Route;

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
