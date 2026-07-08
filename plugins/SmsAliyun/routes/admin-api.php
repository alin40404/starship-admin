<?php

use Illuminate\Support\Facades\Route;
use Modules\System\App\Support\PluginPermissions;
use Plugins\SmsAliyun\Http\Controllers\Admin\SmsAliyunController;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('plugin/sms-aliyun')->group(function () {
        Route::post('test', [SmsAliyunController::class, 'test'])
            ->middleware('admin.permission:'.PluginPermissions::CONFIG);
    });
});
