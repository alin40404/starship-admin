<?php

use Illuminate\Support\Facades\Route;
use Modules\System\App\Http\Controllers\Admin\AdminUserController;
use Modules\System\App\Http\Controllers\Admin\AreaController;
use Modules\System\App\Http\Controllers\Admin\AttachmentController;
use Modules\System\App\Http\Controllers\Admin\AuthController;
use Modules\System\App\Http\Controllers\Admin\ConfigController;
use Modules\System\App\Http\Controllers\Admin\DepartmentController;
use Modules\System\App\Http\Controllers\Admin\DictItemController;
use Modules\System\App\Http\Controllers\Admin\DictTypeController;
use Modules\System\App\Http\Controllers\Admin\MenuController;
use Modules\System\App\Http\Controllers\Admin\PostController;
use Modules\System\App\Http\Controllers\Admin\RoleController;
use Modules\System\App\Http\Controllers\Admin\UserGroupController;
use Modules\System\App\Support\AdminPermissions;
use Modules\System\App\Support\DepartmentPermissions;
use Modules\System\App\Support\MenuPermissions;
use Modules\System\App\Support\PostPermissions;
use Modules\System\App\Support\RolePermissions;
use Modules\System\App\Support\UserGroupPermissions;

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
        Route::get('departments/tree', [DepartmentController::class, 'tree'])
            ->middleware('admin.permission:'.DepartmentPermissions::QUERY);
        Route::get('departments', [DepartmentController::class, 'index'])
            ->middleware('admin.permission:'.DepartmentPermissions::QUERY);
        Route::get('departments/{id}', [DepartmentController::class, 'show'])
            ->middleware('admin.permission:'.DepartmentPermissions::QUERY);
        Route::post('departments', [DepartmentController::class, 'store'])
            ->middleware('admin.permission.department.store');
        Route::post('departments/batch-destroy', [DepartmentController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.DepartmentPermissions::BATCH_DELETE);
        Route::put('departments/batch-status', [DepartmentController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.DepartmentPermissions::BATCH_STATUS);
        Route::put('departments/{id}/status', [DepartmentController::class, 'updateStatus'])
            ->middleware('admin.permission:'.DepartmentPermissions::STATUS);
        Route::put('departments/{id}/sort', [DepartmentController::class, 'updateSort'])
            ->middleware('admin.permission:'.DepartmentPermissions::SORT);
        Route::put('departments/{id}', [DepartmentController::class, 'update'])
            ->middleware('admin.permission:'.DepartmentPermissions::UPDATE);
        Route::delete('departments/{id}', [DepartmentController::class, 'destroy'])
            ->middleware('admin.permission:'.DepartmentPermissions::DELETE);

        Route::get('posts', [PostController::class, 'index'])
            ->middleware('admin.permission:'.PostPermissions::QUERY);
        Route::post('posts', [PostController::class, 'store'])
            ->middleware('admin.permission:'.PostPermissions::CREATE);
        Route::post('posts/batch-destroy', [PostController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.PostPermissions::BATCH_DELETE);
        Route::put('posts/batch-status', [PostController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.PostPermissions::BATCH_STATUS);
        Route::put('posts/{id}', [PostController::class, 'update'])
            ->middleware('admin.permission:'.PostPermissions::UPDATE);
        Route::delete('posts/{id}', [PostController::class, 'destroy'])
            ->middleware('admin.permission:'.PostPermissions::DELETE);

        Route::get('user-groups', [UserGroupController::class, 'index'])
            ->middleware('admin.permission:'.UserGroupPermissions::QUERY);
        Route::post('user-groups', [UserGroupController::class, 'store'])
            ->middleware('admin.permission:'.UserGroupPermissions::CREATE);
        Route::post('user-groups/batch-destroy', [UserGroupController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.UserGroupPermissions::BATCH_DELETE);
        Route::get('user-groups/{id}', [UserGroupController::class, 'show'])
            ->middleware('admin.permission:'.UserGroupPermissions::QUERY);
        Route::put('user-groups/{id}', [UserGroupController::class, 'update'])
            ->middleware('admin.permission:'.UserGroupPermissions::UPDATE);
        Route::delete('user-groups/{id}', [UserGroupController::class, 'destroy'])
            ->middleware('admin.permission:'.UserGroupPermissions::DELETE);
        Route::put('user-groups/{id}/admins', [UserGroupController::class, 'syncAdmins'])
            ->middleware('admin.permission.any:'.UserGroupPermissions::CREATE.','.UserGroupPermissions::UPDATE);

        // 角色管理（/permission/roles）— 权限码见 RolePermissions
        Route::get('roles', [RoleController::class, 'index'])
            ->middleware('admin.permission:'.RolePermissions::QUERY);
        Route::post('roles', [RoleController::class, 'store'])
            ->middleware('admin.permission:'.RolePermissions::CREATE);
        Route::post('roles/batch-destroy', [RoleController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.RolePermissions::BATCH_DELETE);
        Route::put('roles/batch-status', [RoleController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.RolePermissions::BATCH_STATUS);
        Route::get('roles/{id}', [RoleController::class, 'show'])
            ->middleware('admin.permission:'.RolePermissions::QUERY);
        Route::put('roles/{id}/status', [RoleController::class, 'updateStatus'])
            ->middleware('admin.permission:'.RolePermissions::STATUS);
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
            ->middleware('admin.permission:'.MenuPermissions::BATCH_STATUS);
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
        Route::get('admins', [AdminUserController::class, 'index'])
            ->middleware('admin.permission:'.AdminPermissions::QUERY);
        Route::post('admins/batch-destroy', [AdminUserController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.AdminPermissions::BATCH_DELETE);
        Route::put('admins/batch-status', [AdminUserController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.AdminPermissions::BATCH_STATUS);
        Route::post('admins', [AdminUserController::class, 'store'])
            ->middleware('admin.permission:'.AdminPermissions::CREATE);
        Route::put('admins/{id}/status', [AdminUserController::class, 'updateStatus'])
            ->middleware('admin.permission:'.AdminPermissions::STATUS);
        Route::put('admins/{id}', [AdminUserController::class, 'update'])
            ->middleware('admin.permission:'.AdminPermissions::UPDATE);
        Route::delete('admins/{id}', [AdminUserController::class, 'destroy'])
            ->middleware('admin.permission:'.AdminPermissions::DELETE);

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

        Route::get('areas/tree', [AreaController::class, 'tree']);
        Route::get('areas/children', [AreaController::class, 'children']);
        Route::get('areas/{id}', [AreaController::class, 'show']);
        Route::post('areas', [AreaController::class, 'store']);
        Route::put('areas/{id}', [AreaController::class, 'update']);
        Route::delete('areas/{id}', [AreaController::class, 'destroy']);

        Route::get('attachments', [AttachmentController::class, 'index']);
        Route::post('attachments', [AttachmentController::class, 'store']);
    });
});
