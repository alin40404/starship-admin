<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\App\Http\Controllers\Api\PaymentNotifyController;

Route::middleware('api')->prefix('api/shop')->group(function () {
    Route::post('payments/notify', PaymentNotifyController::class)
        ->name('shop.payments.notify');
});
