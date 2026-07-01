<?php

use Illuminate\Support\Facades\Route;
use Modules\System\App\Http\Controllers\Admin\AdminUserController;
use Modules\System\App\Http\Controllers\Admin\AttachmentController;
use Modules\System\App\Http\Controllers\Admin\AuthController;
use Modules\System\App\Http\Controllers\Admin\ConfigController;
use Modules\System\App\Http\Controllers\Admin\DepartmentController;
use Modules\System\App\Http\Controllers\Admin\DictItemController;
use Modules\System\App\Http\Controllers\Admin\DictTypeController;
use Modules\System\App\Http\Controllers\Admin\MenuController;
use Modules\System\App\Http\Controllers\Admin\PostController;
use Modules\System\App\Http\Controllers\Admin\RoleController;
use Modules\System\App\Support\MenuPermissions;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('password', [AuthController::class, 'updatePassword']);
            Route::get('menus', [AuthController::class, 'menus']);
            Route::get('permissions', [AuthController::class, 'permissions']);
        });
    });

    Route::middleware('auth:sanctum')->prefix('system')->group(function () {
        Route::get('departments/tree', [DepartmentController::class, 'tree']);
        Route::get('departments', [DepartmentController::class, 'index']);
        Route::post('departments', [DepartmentController::class, 'store']);
        Route::put('departments/{id}', [DepartmentController::class, 'update']);
        Route::delete('departments/{id}', [DepartmentController::class, 'destroy']);

        Route::get('posts', [PostController::class, 'index']);
        Route::post('posts', [PostController::class, 'store']);
        Route::put('posts/{id}', [PostController::class, 'update']);
        Route::delete('posts/{id}', [PostController::class, 'destroy']);

        Route::get('roles', [RoleController::class, 'index']);
        Route::post('roles', [RoleController::class, 'store']);
        Route::get('roles/{id}', [RoleController::class, 'show']);
        Route::put('roles/{id}', [RoleController::class, 'update']);
        Route::delete('roles/{id}', [RoleController::class, 'destroy']);
        Route::put('roles/{id}/menus', [RoleController::class, 'syncMenus']);

        Route::get('menus/tree', [MenuController::class, 'tree'])
            ->middleware('admin.permission:'.MenuPermissions::QUERY);
        Route::get('menus/path-exists', [MenuController::class, 'pathExists']);
        Route::get('menus', [MenuController::class, 'index'])
            ->middleware('admin.permission:'.MenuPermissions::QUERY);
        Route::post('menus/batch-destroy', [MenuController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.MenuPermissions::BATCH_DELETE);
        Route::put('menus/batch-status', [MenuController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission.menu.batch_status');
        Route::post('menus', [MenuController::class, 'store'])
            ->middleware('admin.permission.menu.store');
        Route::put('menus/{id}/status', [MenuController::class, 'updateStatus'])
            ->middleware('admin.permission:'.MenuPermissions::STATUS);
        Route::put('menus/{id}/sort', [MenuController::class, 'updateSort'])
            ->middleware('admin.permission:'.MenuPermissions::SORT);
        Route::put('menus/{id}', [MenuController::class, 'update'])
            ->middleware('admin.permission:'.MenuPermissions::UPDATE);
        Route::delete('menus/{id}', [MenuController::class, 'destroy'])
            ->middleware('admin.permission:'.MenuPermissions::DELETE);

        Route::get('admins', [AdminUserController::class, 'index']);
        Route::post('admins/batch-destroy', [AdminUserController::class, 'batchDestroy']);
        Route::post('admins', [AdminUserController::class, 'store']);
        Route::put('admins/{id}', [AdminUserController::class, 'update']);
        Route::delete('admins/{id}', [AdminUserController::class, 'destroy']);

        Route::get('configs', [ConfigController::class, 'index']);
        Route::put('configs', [ConfigController::class, 'batchUpsert']);

        Route::get('dict-types', [DictTypeController::class, 'index']);
        Route::post('dict-types', [DictTypeController::class, 'store']);
        Route::put('dict-types/{id}', [DictTypeController::class, 'update']);
        Route::delete('dict-types/{id}', [DictTypeController::class, 'destroy']);

        Route::get('dict-items', [DictItemController::class, 'index']);
        Route::post('dict-items', [DictItemController::class, 'store']);
        Route::put('dict-items/{id}', [DictItemController::class, 'update']);
        Route::delete('dict-items/{id}', [DictItemController::class, 'destroy']);

        Route::get('attachments', [AttachmentController::class, 'index']);
        Route::post('attachments', [AttachmentController::class, 'store']);
    });
});
