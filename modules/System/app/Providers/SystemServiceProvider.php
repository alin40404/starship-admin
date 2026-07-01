<?php

namespace Modules\System\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\System\App\Http\Middleware\EnsureAdminAnyPermission;
use Modules\System\App\Http\Middleware\EnsureAdminDepartmentStorePermission;
use Modules\System\App\Http\Middleware\EnsureAdminMenuBatchStatusPermission;
use Modules\System\App\Http\Middleware\EnsureAdminMenuStorePermission;
use Modules\System\App\Http\Middleware\EnsureAdminPermission;
use Modules\System\App\Http\Middleware\EnsureAdminPostBatchStatusPermission;
use Modules\System\App\Http\Middleware\EnsureAdminRoleBatchStatusPermission;
use Modules\System\App\Http\Middleware\EnsureAdminUserBatchStatusPermission;

class SystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::aliasMiddleware('admin.permission', EnsureAdminPermission::class);
        Route::aliasMiddleware('admin.permission.any', EnsureAdminAnyPermission::class);
        Route::aliasMiddleware('admin.permission.menu.store', EnsureAdminMenuStorePermission::class);
        Route::aliasMiddleware('admin.permission.menu.batch_status', EnsureAdminMenuBatchStatusPermission::class);
        Route::aliasMiddleware('admin.permission.department.store', EnsureAdminDepartmentStorePermission::class);
        Route::aliasMiddleware('admin.permission.post.batch_status', EnsureAdminPostBatchStatusPermission::class);
        Route::aliasMiddleware('admin.permission.role.batch_status', EnsureAdminRoleBatchStatusPermission::class);
        Route::aliasMiddleware('admin.permission.admin.batch_status', EnsureAdminUserBatchStatusPermission::class);

        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'system');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'system');
    }
}
