<?php

use Illuminate\Support\Facades\Route;
use Starship\Core\Http\Controllers\InstallController;
use Starship\Core\Http\Middleware\EnsureNotInstalled;

Route::middleware('api')->prefix('admin-api/install')->group(function () {
    Route::get('status', [InstallController::class, 'status']);

    Route::middleware(EnsureNotInstalled::class)->group(function () {
        Route::get('requirements', [InstallController::class, 'requirements']);
        Route::post('database', [InstallController::class, 'database']);
        Route::post('run', [InstallController::class, 'run']);
    });
});
