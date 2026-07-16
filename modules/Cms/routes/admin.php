<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\App\Http\Controllers\Admin\ArticleController;
use Modules\Cms\App\Http\Controllers\Admin\CategoryController;
use Modules\Cms\App\Support\CmsPermissions;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('cms')->group(function () {
        Route::get('categories/options', [CategoryController::class, 'options'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_QUERY);
        Route::get('categories', [CategoryController::class, 'index'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_QUERY);
        Route::post('categories', [CategoryController::class, 'store'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_CREATE);
        Route::get('categories/{id}', [CategoryController::class, 'show'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_QUERY);
        Route::put('categories/{id}', [CategoryController::class, 'update'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_UPDATE);
        Route::put('categories/{id}/status', [CategoryController::class, 'updateStatus'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_STATUS);
        Route::delete('categories/{id}', [CategoryController::class, 'destroy'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_DELETE);

        Route::get('articles', [ArticleController::class, 'index'])
            ->middleware('admin.permission:'.CmsPermissions::ARTICLES_QUERY);
        Route::post('articles', [ArticleController::class, 'store'])
            ->middleware('admin.permission:'.CmsPermissions::ARTICLES_CREATE);
        Route::get('articles/{id}', [ArticleController::class, 'show'])
            ->middleware('admin.permission:'.CmsPermissions::ARTICLES_QUERY);
        Route::put('articles/{id}', [ArticleController::class, 'update'])
            ->middleware('admin.permission:'.CmsPermissions::ARTICLES_UPDATE);
        Route::put('articles/{id}/status', [ArticleController::class, 'updateStatus'])
            ->middleware('admin.permission:'.CmsPermissions::ARTICLES_STATUS);
        Route::delete('articles/{id}', [ArticleController::class, 'destroy'])
            ->middleware('admin.permission:'.CmsPermissions::ARTICLES_DELETE);
    });
});
