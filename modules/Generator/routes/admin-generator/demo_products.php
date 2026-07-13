<?php

declare(strict_types=1);

/**
 * 由 starship:generator:crud 自动生成
 * 数据表：demo_products
 * 请勿手工修改，重新执行 starship:generator:crud 即可更新
 */

use Illuminate\Support\Facades\Route;
use Modules\Generator\App\Http\Controllers\Admin\ProductsController;
use Modules\Generator\App\Support\ProductsPermissions;

Route::get('products', [ProductsController::class, 'index'])
    ->middleware('admin.permission:'.ProductsPermissions::QUERY);
Route::post('products', [ProductsController::class, 'store'])
    ->middleware('admin.permission:'.ProductsPermissions::CREATE);

Route::post('products/batch-destroy', [ProductsController::class, 'batchDestroy'])
    ->middleware('admin.permission:'.ProductsPermissions::BATCH_DELETE);

Route::put('products/batch-status', [ProductsController::class, 'batchUpdateStatus'])
    ->middleware('admin.permission:'.ProductsPermissions::BATCH_STATUS);

Route::put('products/{id}', [ProductsController::class, 'update'])
    ->middleware('admin.permission:'.ProductsPermissions::UPDATE);
Route::delete('products/{id}', [ProductsController::class, 'destroy'])
    ->middleware('admin.permission:'.ProductsPermissions::DELETE);
