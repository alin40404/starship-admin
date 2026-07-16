<?php

use Illuminate\Support\Facades\Route;
use Plugins\StorageOss\Http\Controllers\Admin\StorageOssController;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('plugin/storage-oss')->group(function () {
        Route::post('test', [StorageOssController::class, 'test'])
            ->middleware('admin.permission:system.plugins.config');
    });
});
