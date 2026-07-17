<?php

use Illuminate\Support\Facades\Route;
use Modules\GameShop\App\Http\Controllers\Admin\GameController;
use Modules\GameShop\App\Http\Controllers\Admin\OrderController;
use Modules\GameShop\App\Http\Controllers\Admin\ProductController;
use Modules\GameShop\App\Support\GameShopPermissions;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('game-shop')->group(function () {
        Route::get('game-options', [GameController::class, 'options']);

        Route::get('games', [GameController::class, 'index'])
            ->middleware('admin.permission:'.GameShopPermissions::GAMES_QUERY);
        Route::post('games', [GameController::class, 'store'])
            ->middleware('admin.permission:'.GameShopPermissions::GAMES_CREATE);
        Route::get('games/{id}', [GameController::class, 'show'])
            ->middleware('admin.permission:'.GameShopPermissions::GAMES_QUERY);
        Route::put('games/{id}', [GameController::class, 'update'])
            ->middleware('admin.permission:'.GameShopPermissions::GAMES_UPDATE);
        Route::put('games/{id}/status', [GameController::class, 'updateStatus'])
            ->middleware('admin.permission:'.GameShopPermissions::GAMES_STATUS);
        Route::delete('games/{id}', [GameController::class, 'destroy'])
            ->middleware('admin.permission:'.GameShopPermissions::GAMES_DELETE);

        Route::get('products', [ProductController::class, 'index'])
            ->middleware('admin.permission:'.GameShopPermissions::PRODUCTS_QUERY);
        Route::post('products', [ProductController::class, 'store'])
            ->middleware('admin.permission:'.GameShopPermissions::PRODUCTS_CREATE);
        Route::get('products/{id}', [ProductController::class, 'show'])
            ->middleware('admin.permission:'.GameShopPermissions::PRODUCTS_QUERY);
        Route::put('products/{id}', [ProductController::class, 'update'])
            ->middleware('admin.permission:'.GameShopPermissions::PRODUCTS_UPDATE);
        Route::put('products/{id}/status', [ProductController::class, 'updateStatus'])
            ->middleware('admin.permission:'.GameShopPermissions::PRODUCTS_STATUS);
        Route::delete('products/{id}', [ProductController::class, 'destroy'])
            ->middleware('admin.permission:'.GameShopPermissions::PRODUCTS_DELETE);

        Route::get('orders', [OrderController::class, 'index'])
            ->middleware('admin.permission:'.GameShopPermissions::ORDERS_QUERY);
        Route::get('orders/{id}', [OrderController::class, 'show'])
            ->middleware('admin.permission:'.GameShopPermissions::ORDERS_QUERY);
    });
});
