<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\App\Http\Controllers\Admin\OrderController;
use Modules\Shop\App\Http\Controllers\Admin\ProductController;
use Modules\Shop\App\Support\ShopPermissions;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('shop')->group(function () {
        Route::get('products', [ProductController::class, 'index'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_QUERY);
        Route::post('products', [ProductController::class, 'store'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_CREATE);
        Route::get('products/{id}', [ProductController::class, 'show'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_QUERY);
        Route::put('products/{id}', [ProductController::class, 'update'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_UPDATE);
        Route::put('products/{id}/status', [ProductController::class, 'updateStatus'])
            ->middleware('admin.permission:'.ShopPermissions::PRODUCTS_STATUS);
        Route::delete('products/{id}', [ProductController::class, 'destroy'])
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
        Route::delete('orders/{id}', [OrderController::class, 'destroy'])
            ->middleware('admin.permission:'.ShopPermissions::ORDERS_DELETE);
    });
});
