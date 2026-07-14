<?php

use Illuminate\Support\Facades\Route;
use Starship\Core\Http\Controllers\LocaleController;
use Starship\Core\Http\Controllers\PluginAssetController;
use Starship\Core\Http\Controllers\ThemeAssetController;

// 不走 web 中间件（无 Session），仅作为 public 未命中时的回落
Route::get('/themes/{slug}/assets/{path}', ThemeAssetController::class)
    ->where('slug', '[A-Za-z0-9][A-Za-z0-9._-]{0,63}')
    ->where('path', '.*')
    ->name('starship.theme.asset');

Route::get('/plugins/{slug}/assets/{path}', PluginAssetController::class)
    ->where('slug', '[A-Za-z0-9][A-Za-z0-9._-]{0,63}')
    ->where('path', '.*')
    ->name('starship.plugin.asset');

Route::middleware('web')->group(function () {
    Route::get('/locale/{locale}', LocaleController::class)
        ->where('locale', 'zh_CN|en')
        ->name('starship.locale');
});
