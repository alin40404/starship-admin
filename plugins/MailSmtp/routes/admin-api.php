<?php

use Illuminate\Support\Facades\Route;
use Plugins\MailSmtp\Http\Controllers\Admin\MailSmtpController;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('plugin/mail-smtp')->group(function () {
        Route::post('test', [MailSmtpController::class, 'test'])
            ->middleware('admin.permission:system.plugins.config');
    });
});
