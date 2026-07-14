<?php

use Illuminate\Support\Facades\Route;
use Modules\Member\App\Http\Controllers\Frontend\AuthController;

Route::middleware('web')->prefix('member')->name('member.')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->name('register.submit');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:member')->group(function () {
        Route::get('account', [AuthController::class, 'account'])->name('account');
    });
});
