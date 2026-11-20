<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\App\Http\Controllers\Admin\BrandController;
use Modules\Shop\App\Http\Controllers\Admin\CategoryController;
use Modules\Shop\App\Http\Controllers\Admin\OrderController;
use Modules\Shop\App\Http\Controllers\Admin\ProductController;
use Modules\Shop\App\Support\ShopPermissions;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('shop')->group(function () {
        Route::get('category-options/tree', [CategoryController::class, 'optionsTree']);

        Route::get('categories/tree', [CategoryController::class, 'tree'])
            ->middleware('admin.permission:'.ShopPermissions::CATEGORIES_QUERY);
        Route::post('categories/batch-destroy', [CategoryController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.ShopPermissions::CATEGORIES_BATCH_DELETE);
        Route::post('categories/batch-status', [CategoryController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.ShopPermissions::CATEGORIES_BATCH_STATUS);
        Route::post('categories', [CategoryController::class, 'store'])
            ->middleware('admin.permission:'.ShopPermissions::CATEGORIES_CREATE);
        Route::get('categories/{id}', [CategoryController::class, 'show'])
            ->middleware('admin.permission:'.ShopPermissions::CATEGORIES_QUERY);
        Route::post('categories/{id}/status', [CategoryController::class, 'updateStatus'])
            ->middleware('admin.permission:'.ShopPermissions::CATEGORIES_STATUS);
        Route::post('categories/{id}/sort', [CategoryController::class, 'updateSort'])
            ->middleware('admin.permission:'.ShopPermissions::CATEGORIES_SORT);
        Route::post('categories/{id}', [CategoryController::class, 'update'])
            ->middleware('admin.permission:'.ShopPermissions::CATEGORIES_UPDATE);
        Route::post('categories/{id}/delete', [CategoryController::class, 'destroy'])
            ->middleware('admin.permission:'.ShopPermissions::CATEGORIES_DELETE);

        Route::get('brand-options', [BrandController::class, 'options']);
        Route::get('brands', [BrandController::class, 'index'])
            ->middleware('admin.permission:'.ShopPermissions::BRANDS_QUERY);
        Route::post('brands', [BrandController::class, 'store'])
            ->middleware('admin.permission:'.ShopPermissions::BRANDS_CREATE);
        Route::get('brands/{id}', [BrandController::class, 'show'])
            ->middleware('admin.permission:'.ShopPermissions::BRANDS_QUERY);
        Route::post('brands/{id}', [BrandController::class, 'update'])
            ->middleware('admin.permission:'.ShopPermissions::BRANDS_UPDATE);
        Route::post('brands/{id}/delete', [BrandController::class, 'destroy'])
            ->middleware('admin.permission:'.ShopPermissions::BRANDS_DELETE);

        Route::get('products', [ProductController::class, 'index'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_QUERY);
        Route::post('products', [ProductController::class, 'store'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_CREATE);
        Route::get('products/{id}', [ProductController::class, 'show'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_QUERY);
        Route::post('products/{id}', [ProductController::class, 'update'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_UPDATE);
        Route::post('products/{id}/status', [ProductController::class, 'updateStatus'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_STATUS);
        Route::post('products/{id}/delete', [ProductController::class, 'destroy'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_DELETE);

        Route::get('orders', [OrderController::class, 'index'])
            ->middleware('admin.permission:'.ShopPermissions::ORDERS_QUERY);
        Route::post('orders', [OrderController::class, 'store'])
            ->middleware('admin.permission:'.ShopPermissions::ORDERS_CREATE);
        Route::get('orders/{id}', [OrderController::class, 'show'])
            ->middleware('admin.permission:'.ShopPermissions::ORDERS_QUERY);
        Route::post('orders/{id}/prepay', [OrderController::class, 'prepay'])
            ->middleware('admin.permission:'.ShopPermissions::ORDERS_PREPAY);
        Route::post('orders/{id}/sync-status', [OrderController::class, 'syncStatus'])
            ->middleware('admin.permission:'.ShopPermissions::ORDERS_PREPAY);
        Route::post('orders/{id}/refund', [OrderController::class, 'refund'])
            ->middleware('admin.permission:'.ShopPermissions::ORDERS_REFUND);
        Route::post('orders/{id}/cancel', [OrderController::class, 'cancel'])
            ->middleware('admin.permission:'.ShopPermissions::ORDERS_CANCEL);
        Route::post('orders/{id}/delete', [OrderController::class, 'destroy'])
            ->middleware('admin.permission:'.ShopPermissions::ORDERS_DELETE);
    });
});
