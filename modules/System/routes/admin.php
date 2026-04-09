<?php

use Illuminate\Support\Facades\Route;
use Modules\System\App\Http\Controllers\AdminUserController;
use Modules\System\App\Http\Controllers\AttachmentController;
use Modules\System\App\Http\Controllers\Auth\AuthController;
use Modules\System\App\Http\Controllers\ConfigController;
use Modules\System\App\Http\Controllers\DepartmentController;
use Modules\System\App\Http\Controllers\DictItemController;
use Modules\System\App\Http\Controllers\DictTypeController;
use Modules\System\App\Http\Controllers\MenuController;
use Modules\System\App\Http\Controllers\PostController;
use Modules\System\App\Http\Controllers\RoleController;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
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
        Route::put('roles/{id}', [RoleController::class, 'update']);
        Route::delete('roles/{id}', [RoleController::class, 'destroy']);
        Route::put('roles/{id}/menus', [RoleController::class, 'syncMenus']);

        Route::get('menus/tree', [MenuController::class, 'tree']);
        Route::get('menus', [MenuController::class, 'index']);
        Route::post('menus', [MenuController::class, 'store']);
        Route::put('menus/{id}', [MenuController::class, 'update']);
        Route::delete('menus/{id}', [MenuController::class, 'destroy']);

        Route::get('admins', [AdminUserController::class, 'index']);
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
