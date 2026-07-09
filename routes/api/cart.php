<?php

use Illuminate\Support\Facades\Route;

// Cart routes (auth required)
Route::middleware('auth:sanctum')->prefix('cart')->as('api.cart.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\CartController::class, 'index'])->name('index');
    Route::post('/add', [\App\Http\Controllers\Api\CartController::class, 'add'])->name('add');
    Route::delete('/remove/{id}', [\App\Http\Controllers\Api\CartController::class, 'remove'])->name('remove');
    Route::delete('/clear', [\App\Http\Controllers\Api\CartController::class, 'clear'])->name('clear');
    Route::post('/discount/apply', [\App\Http\Controllers\Api\DiscountController::class, 'applyDiscount'])->name('discount.apply');
    Route::post('/discount/remove', [\App\Http\Controllers\Api\DiscountController::class, 'removeDiscount'])->name('discount.remove');
    // Fixed path: avoid double '/cart/cart/validate-discount'
    Route::post('/validate-discount', [\App\Http\Controllers\Api\DiscountController::class, 'validateDiscount'])->name('validate-discount');
});

// Payment
Route::middleware('auth:sanctum')->post('/cart/pay', [\App\Http\Controllers\Api\PaymentController::class, 'store']);
Route::middleware('auth:sanctum')->prefix('payment')->as('api.payment.')->group(function () {
    Route::post('/retry/{uuid}', [\App\Http\Controllers\Api\PaymentController::class, 'retry'])->name('retry');
    Route::post('/receipt/detail', [\App\Http\Controllers\Api\PaymentController::class, 'receipt'])->name('receipt.detail');
});
Route::match(['get', 'post'], '/payment/verify/{uuid}', [\App\Http\Controllers\Api\PaymentController::class, 'callback'])->name('api.payment.callback');

// Legacy cart payment callback
Route::get('/cart/payment/callback', [\App\Http\Controllers\Api\CartController::class, 'callback'])->name('api.payment-callback');


