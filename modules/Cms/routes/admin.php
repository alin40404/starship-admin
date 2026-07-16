<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\App\Http\Controllers\Admin\ArticleController;
use Modules\Cms\App\Http\Controllers\Admin\CategoryController;
use Modules\Cms\App\Support\CmsPermissions;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('cms')->group(function () {
        // 选择器：登录即可，无需栏目管理按钮权限（文章表单用）
        Route::get('category-options/tree', [CategoryController::class, 'optionsTree']);

        Route::get('categories/tree', [CategoryController::class, 'tree'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_QUERY);
        Route::post('categories/batch-destroy', [CategoryController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_BATCH_DELETE);
        Route::put('categories/batch-status', [CategoryController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_BATCH_STATUS);
        Route::post('categories', [CategoryController::class, 'store'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_CREATE);
        Route::get('categories/{id}', [CategoryController::class, 'show'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_QUERY);
        Route::put('categories/{id}/status', [CategoryController::class, 'updateStatus'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_STATUS);
        Route::put('categories/{id}/sort', [CategoryController::class, 'updateSort'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_SORT);
        Route::put('categories/{id}', [CategoryController::class, 'update'])
            ->middleware('admin.permission:'.CmsPermissions::CATEGORIES_UPDATE);
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
