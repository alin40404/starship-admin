<?php

use Illuminate\Support\Facades\Route;
use Modules\System\App\Http\Controllers\Admin\AdminUserController;
use Modules\System\App\Http\Controllers\Admin\AreaController;
use Modules\System\App\Http\Controllers\Admin\CategoryController;
use Modules\System\App\Http\Controllers\Admin\AttachmentController;
use Modules\System\App\Http\Controllers\Admin\AuthController;
use Modules\System\App\Http\Controllers\Admin\ConfigController;
use Modules\System\App\Http\Controllers\Admin\DepartmentController;
use Modules\System\App\Http\Controllers\Admin\DevModuleController;
use Modules\System\App\Http\Controllers\Admin\DictItemController;
use Modules\System\App\Http\Controllers\Admin\DictTypeController;
use Modules\System\App\Http\Controllers\Admin\ExceptionLogController;
use Modules\System\App\Http\Controllers\Admin\LoginLogController;
use Modules\System\App\Http\Controllers\Admin\OperationLogController;
use Modules\System\App\Http\Controllers\Admin\InboxMessageController;
use Modules\System\App\Http\Controllers\Admin\MenuController;
use Modules\System\App\Http\Controllers\Admin\PluginController;
use Modules\System\App\Http\Controllers\Admin\PostController;
use Modules\System\App\Http\Controllers\Admin\ThemeController;
use Modules\System\App\Http\Controllers\Admin\RoleController;
use Modules\System\App\Http\Controllers\Admin\UserGroupController;
use Modules\System\App\Support\AdminPermissions;
use Modules\System\App\Support\AreaPermissions;
use Modules\System\App\Support\CategoryPermissions;
use Modules\System\App\Support\AttachmentPermissions;
use Modules\System\App\Support\ConfigPermissions;
use Modules\System\App\Support\DepartmentPermissions;
use Modules\System\App\Support\DictPermissions;
use Modules\System\App\Support\ExceptionLogPermissions;
use Modules\System\App\Support\LoginLogPermissions;
use Modules\System\App\Support\OperationLogPermissions;
use Modules\System\App\Support\InboxPermissions;
use Modules\System\App\Support\MenuPermissions;
use Modules\System\App\Support\ModulePermissions;
use Modules\System\App\Support\PluginPermissions;
use Modules\System\App\Support\PostPermissions;
use Modules\System\App\Support\RolePermissions;
use Modules\System\App\Support\ThemePermissions;
use Modules\System\App\Support\UserGroupPermissions;

// 探活：仅走 api 中间件组，不套维护模式 / IP 黑名单
Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::get('ping', function () {
        $payload = [
            'message' => 'Starship Admin is running',
        ];

        if (config('app.debug')) {
            $payload['env'] = app()->environment();
        }

        return response()->json($payload);
    });
});

Route::middleware(['api', 'admin.installed', 'admin.forbidden_ip', 'admin.maintenance'])->prefix('admin-api')->group(function () {
    // --- start: 无需登录 ---
    Route::get('system/site', [ConfigController::class, 'site']);
    Route::get('system/runtime', [ConfigController::class, 'runtime']);
    // --- end: 无需登录 ---

    // --- start: 认证与会话（auth/*） ---
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('sms-code', [AuthController::class, 'sendSmsCode']);
        Route::post('sms-login', [AuthController::class, 'smsLogin']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::get('reset-password/verify', [AuthController::class, 'verifyResetPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('profile', [AuthController::class, 'updateProfile']);
            Route::post('password', [AuthController::class, 'updatePassword']);
            Route::get('menus', [AuthController::class, 'menus']);
            Route::get('permissions', [AuthController::class, 'permissions']);
        });
    });
    // --- end: 认证与会话（auth/*） ---

    Route::middleware('auth:sanctum')->prefix('system')->group(function () {
        // --- start: 需登录，无按钮权限（表单选择器 / 联动 / 字典与附件选择等） ---
        Route::get('dict-options/{code}', [DictTypeController::class, 'options']);

        Route::get('department-options/tree', [DepartmentController::class, 'optionsTree']);

        Route::get('menu-options/tree', [MenuController::class, 'optionsTree']);
        Route::get('menus/path-exists', [MenuController::class, 'pathExists']);

        Route::get('area-options/tree', [AreaController::class, 'optionsTree']);
        Route::get('area-options/children', [AreaController::class, 'optionsChildren']);
        Route::get('area-options/cascader-init', [AreaController::class, 'optionsCascaderInit']);
        Route::get('area-options/cascader-options', [AreaController::class, 'optionsCascaderOptions']);

        Route::get('category-options/types', [CategoryController::class, 'optionsTypes']);
        Route::get('category-options/tree', [CategoryController::class, 'optionsTree']);

        Route::get('attachment-options/categories', [AttachmentController::class, 'optionsCategories']);
        Route::get('attachments/picker', [AttachmentController::class, 'pickerIndex']);
        // --- end: 需登录，无按钮权限 ---

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
        Route::post('departments/batch-status', [DepartmentController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.DepartmentPermissions::BATCH_STATUS);
        Route::post('departments/{id}/status', [DepartmentController::class, 'updateStatus'])
            ->middleware('admin.permission:'.DepartmentPermissions::STATUS);
        Route::post('departments/{id}/sort', [DepartmentController::class, 'updateSort'])
            ->middleware('admin.permission:'.DepartmentPermissions::SORT);
        Route::post('departments/{id}', [DepartmentController::class, 'update'])
            ->middleware('admin.permission:'.DepartmentPermissions::UPDATE);
        Route::post('departments/{id}/delete', [DepartmentController::class, 'destroy'])
            ->middleware('admin.permission:'.DepartmentPermissions::DELETE);

        Route::get('posts', [PostController::class, 'index'])
            ->middleware('admin.permission:'.PostPermissions::QUERY);
        Route::post('posts', [PostController::class, 'store'])
            ->middleware('admin.permission:'.PostPermissions::CREATE);
        Route::post('posts/batch-destroy', [PostController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.PostPermissions::BATCH_DELETE);
        Route::post('posts/batch-status', [PostController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.PostPermissions::BATCH_STATUS);
        Route::post('posts/{id}', [PostController::class, 'update'])
            ->middleware('admin.permission:'.PostPermissions::UPDATE);
        Route::post('posts/{id}/delete', [PostController::class, 'destroy'])
            ->middleware('admin.permission:'.PostPermissions::DELETE);

        Route::get('user-groups', [UserGroupController::class, 'index'])
            ->middleware('admin.permission:'.UserGroupPermissions::QUERY);
        Route::post('user-groups', [UserGroupController::class, 'store'])
            ->middleware('admin.permission:'.UserGroupPermissions::CREATE);
        Route::post('user-groups/batch-destroy', [UserGroupController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.UserGroupPermissions::BATCH_DELETE);
        Route::get('user-groups/{id}', [UserGroupController::class, 'show'])
            ->middleware('admin.permission:'.UserGroupPermissions::QUERY);
        Route::post('user-groups/{id}', [UserGroupController::class, 'update'])
            ->middleware('admin.permission:'.UserGroupPermissions::UPDATE);
        Route::post('user-groups/{id}/delete', [UserGroupController::class, 'destroy'])
            ->middleware('admin.permission:'.UserGroupPermissions::DELETE);
        Route::post('user-groups/{id}/admins', [UserGroupController::class, 'syncAdmins'])
            ->middleware('admin.permission.any:'.UserGroupPermissions::CREATE.','.UserGroupPermissions::UPDATE);

        // 角色管理（/permission/roles）— 权限码见 RolePermissions
        Route::get('roles', [RoleController::class, 'index'])
            ->middleware('admin.permission:'.RolePermissions::QUERY);
        Route::post('roles', [RoleController::class, 'store'])
            ->middleware('admin.permission:'.RolePermissions::CREATE);
        Route::post('roles/batch-destroy', [RoleController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.RolePermissions::BATCH_DELETE);
        Route::post('roles/batch-status', [RoleController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.RolePermissions::BATCH_STATUS);
        Route::get('roles/{id}', [RoleController::class, 'show'])
            ->middleware('admin.permission:'.RolePermissions::QUERY);
        Route::post('roles/{id}/status', [RoleController::class, 'updateStatus'])
            ->middleware('admin.permission:'.RolePermissions::STATUS);
        Route::post('roles/{id}', [RoleController::class, 'update'])
            ->middleware('admin.permission:'.RolePermissions::UPDATE);
        Route::post('roles/{id}/delete', [RoleController::class, 'destroy'])
            ->middleware('admin.permission:'.RolePermissions::DELETE);
        // 同步角色菜单权限：新增或编辑角色时均可调用
        Route::post('roles/{id}/menus', [RoleController::class, 'syncMenus'])
            ->middleware('admin.permission.any:'.RolePermissions::CREATE.','.RolePermissions::UPDATE);
        Route::post('roles/{id}/depts', [RoleController::class, 'syncDepts'])
            ->middleware('admin.permission.any:'.RolePermissions::CREATE.','.RolePermissions::UPDATE);

        // 菜单管理（/permission/menus）— 权限码见 MenuPermissions
        Route::get('menus/tree', [MenuController::class, 'tree'])
            ->middleware('admin.permission:'.MenuPermissions::QUERY);
        Route::get('menus', [MenuController::class, 'index'])
            ->middleware('admin.permission:'.MenuPermissions::QUERY);
        Route::post('menus/batch-destroy', [MenuController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.MenuPermissions::BATCH_DELETE);
        Route::post('menus/batch-status', [MenuController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.MenuPermissions::BATCH_STATUS);
        // 新增菜单：parent_id > 0 走 append，否则走 create（见 EnsureAdminMenuStorePermission）
        Route::post('menus', [MenuController::class, 'store'])
            ->middleware('admin.permission.menu.store');
        Route::post('menus/{id}/status', [MenuController::class, 'updateStatus'])
            ->middleware('admin.permission:'.MenuPermissions::STATUS);
        Route::post('menus/{id}/sort', [MenuController::class, 'updateSort'])
            ->middleware('admin.permission:'.MenuPermissions::SORT);
        Route::post('menus/{id}', [MenuController::class, 'update'])
            ->middleware('admin.permission:'.MenuPermissions::UPDATE);
        Route::post('menus/{id}/delete', [MenuController::class, 'destroy'])
            ->middleware('admin.permission:'.MenuPermissions::DELETE);
        Route::get('admins', [AdminUserController::class, 'index'])
            ->middleware('admin.permission:'.AdminPermissions::QUERY);
        Route::post('admins/batch-destroy', [AdminUserController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.AdminPermissions::BATCH_DELETE);
        Route::post('admins/batch-status', [AdminUserController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.AdminPermissions::BATCH_STATUS);
        Route::post('admins', [AdminUserController::class, 'store'])
            ->middleware('admin.permission:'.AdminPermissions::CREATE);
        Route::post('admins/{id}/status', [AdminUserController::class, 'updateStatus'])
            ->middleware('admin.permission:'.AdminPermissions::STATUS);
        Route::post('admins/{id}', [AdminUserController::class, 'update'])
            ->middleware('admin.permission:'.AdminPermissions::UPDATE);
        Route::post('admins/{id}/delete', [AdminUserController::class, 'destroy'])
            ->middleware('admin.permission:'.AdminPermissions::DELETE);

        Route::get('configs', [ConfigController::class, 'index'])
            ->middleware('admin.permission:'.ConfigPermissions::QUERY);
        Route::post('configs', [ConfigController::class, 'batchUpsert'])
            ->middleware('admin.permission:'.ConfigPermissions::UPDATE);
        Route::post('configs/sms-test', [ConfigController::class, 'testSms'])
            ->middleware('admin.permission:'.ConfigPermissions::UPDATE);
        Route::post('configs/mail-test', [ConfigController::class, 'testMail'])
            ->middleware('admin.permission:'.ConfigPermissions::UPDATE);
        Route::post('configs/batch-destroy', [ConfigController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.ConfigPermissions::BATCH_DELETE);
        Route::post('configs/{id}/delete', [ConfigController::class, 'destroy'])
            ->middleware('admin.permission:'.ConfigPermissions::DELETE);

        Route::get('dict-types', [DictTypeController::class, 'index'])
            ->middleware('admin.permission:'.DictPermissions::QUERY);
        Route::post('dict-types', [DictTypeController::class, 'store'])
            ->middleware('admin.permission:'.DictPermissions::CREATE);
        Route::post('dict-types/{id}', [DictTypeController::class, 'update'])
            ->middleware('admin.permission:'.DictPermissions::UPDATE);
        Route::post('dict-types/{id}/delete', [DictTypeController::class, 'destroy'])
            ->middleware('admin.permission:'.DictPermissions::DELETE);

        Route::get('dict-items', [DictItemController::class, 'index'])
            ->middleware('admin.permission:'.DictPermissions::QUERY);
        Route::post('dict-items', [DictItemController::class, 'store'])
            ->middleware('admin.permission:'.DictPermissions::CREATE);
        Route::post('dict-items/{id}', [DictItemController::class, 'update'])
            ->middleware('admin.permission:'.DictPermissions::UPDATE);
        Route::post('dict-items/{id}/delete', [DictItemController::class, 'destroy'])
            ->middleware('admin.permission:'.DictPermissions::DELETE);

        Route::get('areas/tree', [AreaController::class, 'tree'])
            ->middleware('admin.permission:'.AreaPermissions::QUERY);
        Route::get('areas/children', [AreaController::class, 'children'])
            ->middleware('admin.permission:'.AreaPermissions::QUERY);
        Route::get('areas/cascader-init', [AreaController::class, 'cascaderInit'])
            ->middleware('admin.permission:'.AreaPermissions::QUERY);
        Route::get('areas/cascader-options', [AreaController::class, 'cascaderOptions'])
            ->middleware('admin.permission:'.AreaPermissions::QUERY);
        Route::get('areas/pinyin-preview', [AreaController::class, 'pinyinPreview'])
            ->middleware('admin.permission:'.AreaPermissions::QUERY);
        Route::post('areas/batch-destroy', [AreaController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.AreaPermissions::BATCH_DELETE);
        Route::get('areas/{id}', [AreaController::class, 'show'])
            ->middleware('admin.permission:'.AreaPermissions::QUERY);
        Route::post('areas', [AreaController::class, 'store'])
            ->middleware('admin.permission.area.store');
        Route::post('areas/{id}', [AreaController::class, 'update'])
            ->middleware('admin.permission:'.AreaPermissions::UPDATE);
        Route::post('areas/{id}/delete', [AreaController::class, 'destroy'])
            ->middleware('admin.permission:'.AreaPermissions::DELETE);

        Route::get('attachments', [AttachmentController::class, 'index'])
            ->middleware('admin.permission:'.AttachmentPermissions::QUERY);
        Route::post('attachments', [AttachmentController::class, 'store'])
            ->middleware('admin.permission:'.AttachmentPermissions::UPLOAD);
        Route::post('attachments/batch-destroy', [AttachmentController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.AttachmentPermissions::BATCH_DELETE);
        Route::post('attachments/{id}/delete', [AttachmentController::class, 'destroy'])
            ->middleware('admin.permission:'.AttachmentPermissions::DELETE);

        Route::get('categories/tree', [CategoryController::class, 'tree'])
            ->middleware('admin.permission:'.CategoryPermissions::QUERY);
        Route::get('categories/{id}', [CategoryController::class, 'show'])
            ->middleware('admin.permission:'.CategoryPermissions::QUERY);
        Route::post('categories', [CategoryController::class, 'store'])
            ->middleware('admin.permission:'.CategoryPermissions::CREATE);
        Route::post('categories/batch-destroy', [CategoryController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.CategoryPermissions::BATCH_DELETE);
        Route::post('categories/batch-status', [CategoryController::class, 'batchUpdateStatus'])
            ->middleware('admin.permission:'.CategoryPermissions::BATCH_STATUS);
        Route::post('categories/{id}/status', [CategoryController::class, 'updateStatus'])
            ->middleware('admin.permission:'.CategoryPermissions::STATUS);
        Route::post('categories/{id}/sort', [CategoryController::class, 'updateSort'])
            ->middleware('admin.permission:'.CategoryPermissions::SORT);
        Route::post('categories/{id}', [CategoryController::class, 'update'])
            ->middleware('admin.permission:'.CategoryPermissions::UPDATE);
        Route::post('categories/{id}/delete', [CategoryController::class, 'destroy'])
            ->middleware('admin.permission:'.CategoryPermissions::DELETE);
    });

    // --- start: 代码生成 CRUD（admin-generator/*） ---
    Route::middleware('auth:sanctum')->prefix('system')->group(function () {
        require __DIR__.'/admin-generator/_loader.php';
    });
    // --- end: 代码生成 CRUD（admin-generator/*） ---

    Route::middleware('auth:sanctum')->prefix('dev')->group(function () {
        // 模块管理（/dev/modules）— 扫描 modules/*/module.json
        Route::get('modules', [DevModuleController::class, 'index'])
            ->middleware('admin.permission:'.ModulePermissions::QUERY);
        Route::get('modules/{name}', [DevModuleController::class, 'show'])
            ->middleware('admin.permission:'.ModulePermissions::QUERY);
        Route::post('modules/{name}/status', [DevModuleController::class, 'updateStatus'])
            ->middleware('admin.permission:'.ModulePermissions::UPDATE);

        Route::get('plugins', [PluginController::class, 'index'])
            ->middleware('admin.permission:'.PluginPermissions::QUERY);
        Route::post('plugins/discover', [PluginController::class, 'discover'])
            ->middleware('admin.permission:'.PluginPermissions::DISCOVER);
        Route::get('plugins/{slug}', [PluginController::class, 'show'])
            ->middleware('admin.permission:'.PluginPermissions::QUERY);
        Route::get('plugins/{slug}/cover', [PluginController::class, 'cover'])
            ->middleware('admin.permission:'.PluginPermissions::QUERY);
        Route::post('plugins/{slug}/install', [PluginController::class, 'install'])
            ->middleware('admin.permission:'.PluginPermissions::INSTALL);
        Route::post('plugins/{slug}/enable', [PluginController::class, 'enable'])
            ->middleware('admin.permission:'.PluginPermissions::ENABLE);
        Route::post('plugins/{slug}/disable', [PluginController::class, 'disable'])
            ->middleware('admin.permission:'.PluginPermissions::DISABLE);
        Route::post('plugins/{slug}/uninstall', [PluginController::class, 'uninstall'])
            ->middleware('admin.permission:'.PluginPermissions::UNINSTALL);
        Route::get('plugins/{slug}/config', [PluginController::class, 'config'])
            ->middleware('admin.permission:'.PluginPermissions::CONFIG);
        Route::post('plugins/{slug}/config', [PluginController::class, 'updateConfig'])
            ->middleware('admin.permission:'.PluginPermissions::CONFIG);

        Route::get('themes', [ThemeController::class, 'index'])
            ->middleware('admin.permission:'.ThemePermissions::QUERY);
        Route::post('themes/discover', [ThemeController::class, 'discover'])
            ->middleware('admin.permission:'.ThemePermissions::DISCOVER);
        Route::get('themes/{slug}', [ThemeController::class, 'show'])
            ->middleware('admin.permission:'.ThemePermissions::QUERY);
        Route::get('themes/{slug}/cover', [ThemeController::class, 'cover'])
            ->middleware('admin.permission:'.ThemePermissions::QUERY);
        Route::post('themes/{slug}/install', [ThemeController::class, 'install'])
            ->middleware('admin.permission:'.ThemePermissions::INSTALL);
        Route::post('themes/{slug}/enable', [ThemeController::class, 'enable'])
            ->middleware('admin.permission:'.ThemePermissions::ENABLE);
        Route::post('themes/{slug}/disable', [ThemeController::class, 'disable'])
            ->middleware('admin.permission:'.ThemePermissions::DISABLE);
        Route::post('themes/{slug}/uninstall', [ThemeController::class, 'uninstall'])
            ->middleware('admin.permission:'.ThemePermissions::UNINSTALL);
        Route::post('themes/{slug}/delete', [ThemeController::class, 'delete'])
            ->middleware('admin.permission:'.ThemePermissions::DELETE);
        Route::post('themes/{slug}/switch', [ThemeController::class, 'switch'])
            ->middleware('admin.permission:'.ThemePermissions::SWITCH);
    });

    // --- start: 监控中心（monitor/*） ---
    Route::middleware('auth:sanctum')->prefix('monitor')->group(function () {
        Route::get('operation-logs', [OperationLogController::class, 'index'])
            ->middleware('admin.permission:'.OperationLogPermissions::QUERY);
        Route::get('operation-logs/{id}', [OperationLogController::class, 'show'])
            ->middleware('admin.permission:'.OperationLogPermissions::QUERY);
        Route::post('operation-logs/{id}/delete', [OperationLogController::class, 'destroy'])
            ->middleware('admin.permission:'.OperationLogPermissions::DELETE);
        Route::post('operation-logs/batch-destroy', [OperationLogController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.OperationLogPermissions::BATCH_DELETE);

        Route::get('login-logs', [LoginLogController::class, 'index'])
            ->middleware('admin.permission:'.LoginLogPermissions::QUERY);
        Route::get('login-logs/{id}', [LoginLogController::class, 'show'])
            ->middleware('admin.permission:'.LoginLogPermissions::QUERY);
        Route::post('login-logs/{id}/delete', [LoginLogController::class, 'destroy'])
            ->middleware('admin.permission:'.LoginLogPermissions::DELETE);
        Route::post('login-logs/batch-destroy', [LoginLogController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.LoginLogPermissions::BATCH_DELETE);

        Route::get('exception-logs', [ExceptionLogController::class, 'index'])
            ->middleware('admin.permission:'.ExceptionLogPermissions::QUERY);
        Route::get('exception-logs/{id}', [ExceptionLogController::class, 'show'])
            ->middleware('admin.permission:'.ExceptionLogPermissions::QUERY);
        Route::post('exception-logs/{id}/delete', [ExceptionLogController::class, 'destroy'])
            ->middleware('admin.permission:'.ExceptionLogPermissions::DELETE);
        Route::post('exception-logs/batch-destroy', [ExceptionLogController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.ExceptionLogPermissions::BATCH_DELETE);
    });
    // --- end: 监控中心（monitor/*） ---

    // --- start: 站内信（inbox/*） ---
    Route::middleware('auth:sanctum')->prefix('inbox')->group(function () {
        Route::get('messages', [InboxMessageController::class, 'index'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::get('messages/recent', [InboxMessageController::class, 'recent'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::get('messages/unread-count', [InboxMessageController::class, 'unreadCount'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::get('messages/category-stats', [InboxMessageController::class, 'categoryStats'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::get('messages/settings', [InboxMessageController::class, 'settings'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::post('messages/read-all', [InboxMessageController::class, 'markAllRead'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::post('messages/clear-read', [InboxMessageController::class, 'clearRead'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::get('messages/{id}', [InboxMessageController::class, 'show'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::post('messages/{id}/read', [InboxMessageController::class, 'markRead'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::post('messages/{id}/delete', [InboxMessageController::class, 'destroy'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::post('messages/batch-read', [InboxMessageController::class, 'batchMarkRead'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
        Route::post('messages/batch-destroy', [InboxMessageController::class, 'batchDestroy'])
            ->middleware('admin.permission:'.InboxPermissions::QUERY);
    });
    // --- end: 站内信（inbox/*） ---
});
