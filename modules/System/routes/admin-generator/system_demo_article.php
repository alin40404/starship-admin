<?php

declare(strict_types=1);

/**
 * 由 generator:crud 自动生成
 * 数据表：sa_system_demo_article
 * 请勿手工修改，重新执行 generator:crud 即可更新
 */

use Illuminate\Support\Facades\Route;
use Modules\System\App\Http\Controllers\Admin\DemoArticleController;
use Modules\System\App\Support\DemoArticlePermissions;

Route::get('demo_articles', [DemoArticleController::class, 'index'])
    ->middleware('admin.permission:'.DemoArticlePermissions::QUERY);
Route::post('demo_articles', [DemoArticleController::class, 'store'])
    ->middleware('admin.permission:'.DemoArticlePermissions::CREATE);

Route::post('demo_articles/batch-destroy', [DemoArticleController::class, 'batchDestroy'])
    ->middleware('admin.permission:'.DemoArticlePermissions::BATCH_DELETE);

Route::put('demo_articles/batch-status', [DemoArticleController::class, 'batchUpdateStatus'])
    ->middleware('admin.permission:'.DemoArticlePermissions::BATCH_STATUS);

Route::put('demo_articles/{id}', [DemoArticleController::class, 'update'])
    ->middleware('admin.permission:'.DemoArticlePermissions::UPDATE);
Route::delete('demo_articles/{id}', [DemoArticleController::class, 'destroy'])
    ->middleware('admin.permission:'.DemoArticlePermissions::DELETE);
