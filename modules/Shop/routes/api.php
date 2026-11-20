<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\App\Http\Controllers\Api\CartController;
use Modules\Shop\App\Http\Controllers\Api\PaymentNotifyController;

Route::middleware('api')->prefix('api/shop')->group(function () {
    Route::post('payments/notify', PaymentNotifyController::class)
        ->name('shop.payments.notify');

    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'addItem']);
    Route::post('cart/items/{itemId}', [CartController::class, 'updateItem']);
    Route::post('cart/items/{itemId}/delete', [CartController::class, 'removeItem']);
    Route::post('cart/checkout', [CartController::class, 'checkout']);
});
