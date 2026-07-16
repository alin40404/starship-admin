<?php

use Illuminate\Support\Facades\Route;
use Plugins\PaymentWechat\Http\Controllers\Admin\PaymentWechatController;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('plugin/payment-wechat')->group(function () {
        Route::post('test', [PaymentWechatController::class, 'test'])
            ->middleware('admin.permission:system.plugins.config');
    });
});
