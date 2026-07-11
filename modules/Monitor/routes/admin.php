<?php

use Illuminate\Support\Facades\Route;
use Modules\Monitor\App\Http\Controllers\Admin\ExceptionLogController;
use Modules\Monitor\App\Http\Controllers\Admin\LoginLogController;
use Modules\Monitor\App\Http\Controllers\Admin\OperationLogController;
use Modules\Monitor\App\Support\ExceptionLogPermissions;
use Modules\Monitor\App\Support\LoginLogPermissions;
use Modules\Monitor\App\Support\OperationLogPermissions;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('monitor')->group(function () {
        Route::get('operation-logs', [OperationLogController::class, 'index'])
            ->middleware('admin.permission:'.OperationLogPermissions::QUERY);
        Route::get('operation-logs/{id}', [OperationLogController::class, 'show'])
            ->middleware('admin.permission:'.OperationLogPermissions::QUERY);
        Route::delete('operation-logs/{id}', [OperationLogController::class, 'destroy'])
            ->middleware('admin.permission:'.OperationLogPermissions::DELETE);
        Route::post('operation-logs/batch-destroy', [OperationLogController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.OperationLogPermissions::BATCH_DELETE);

        Route::get('login-logs', [LoginLogController::class, 'index'])
            ->middleware('admin.permission:'.LoginLogPermissions::QUERY);
        Route::get('login-logs/{id}', [LoginLogController::class, 'show'])
            ->middleware('admin.permission:'.LoginLogPermissions::QUERY);
        Route::delete('login-logs/{id}', [LoginLogController::class, 'destroy'])
            ->middleware('admin.permission:'.LoginLogPermissions::DELETE);
        Route::post('login-logs/batch-destroy', [LoginLogController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.LoginLogPermissions::BATCH_DELETE);

        Route::get('exception-logs', [ExceptionLogController::class, 'index'])
            ->middleware('admin.permission:'.ExceptionLogPermissions::QUERY);
        Route::get('exception-logs/{id}', [ExceptionLogController::class, 'show'])
            ->middleware('admin.permission:'.ExceptionLogPermissions::QUERY);
        Route::delete('exception-logs/{id}', [ExceptionLogController::class, 'destroy'])
            ->middleware('admin.permission:'.ExceptionLogPermissions::DELETE);
        Route::post('exception-logs/batch-destroy', [ExceptionLogController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.ExceptionLogPermissions::BATCH_DELETE);
    });
});
