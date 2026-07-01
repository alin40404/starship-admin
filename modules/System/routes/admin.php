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
use Modules\System\App\Support\RolePermissions;

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

        // 角色管理（/permission/roles）— 权限码见 RolePermissions
        Route::get('roles', [RoleController::class, 'index'])
            ->middleware('admin.permission:'.RolePermissions::QUERY);
        Route::post('roles', [RoleController::class, 'store'])
            ->middleware('admin.permission:'.RolePermissions::CREATE);
        Route::post('roles/batch-destroy', [RoleController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.RolePermissions::BATCH_DELETE);
        Route::put('roles/batch-status', [RoleController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission.role.batch_status');
        Route::get('roles/{id}', [RoleController::class, 'show'])
            ->middleware('admin.permission:'.RolePermissions::QUERY);
        Route::put('roles/{id}', [RoleController::class, 'update'])
            ->middleware('admin.permission:'.RolePermissions::UPDATE);
        Route::delete('roles/{id}', [RoleController::class, 'destroy'])
            ->middleware('admin.permission:'.RolePermissions::DELETE);
        // 同步角色菜单权限：新增或编辑角色时均可调用
        Route::put('roles/{id}/menus', [RoleController::class, 'syncMenus'])
            ->middleware('admin.permission.any:'.RolePermissions::CREATE.','.RolePermissions::UPDATE);
        Route::put('roles/{id}/depts', [RoleController::class, 'syncDepts'])
            ->middleware('admin.permission.any:'.RolePermissions::CREATE.','.RolePermissions::UPDATE);

        // 菜单管理（/permission/menus）— 权限码见 MenuPermissions
        Route::get('menus/tree', [MenuController::class, 'tree'])
            ->middleware('admin.permission:'.MenuPermissions::QUERY);
        // 校验路由路径是否已存在，表单联动用，不做按钮权限拦截
        Route::get('menus/path-exists', [MenuController::class, 'pathExists']);
        Route::get('menus', [MenuController::class, 'index'])
            ->middleware('admin.permission:'.MenuPermissions::QUERY);
        Route::post('menus/batch-destroy', [MenuController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.MenuPermissions::BATCH_DELETE);
        Route::put('menus/batch-status', [MenuController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission.menu.batch_status');
        // 新增菜单：parent_id > 0 走 append，否则走 create（见 EnsureAdminMenuStorePermission）
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
