<?php

use Illuminate\Support\Facades\Route;
use Modules\System\App\Http\Controllers\Frontend\HomeController;
use Modules\System\App\Http\Controllers\Frontend\LocaleController;

/*
|--------------------------------------------------------------------------
| System 模块 — Web 路由（Blade / 主题前台等）
|--------------------------------------------------------------------------
*/

Route::middleware('web')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('frontend.home');

    Route::get('/locale/{locale}', LocaleController::class)
        ->where('locale', implode('|', config('system.locales', ['zh_CN', 'en'])))
        ->name('system.locale');
});
