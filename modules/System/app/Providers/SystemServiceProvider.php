<?php

namespace Modules\System\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\System\App\Http\Middleware\EnsureAdminAnyPermission;
use Modules\System\App\Http\Middleware\EnsureAdminAreaStorePermission;
use Modules\System\App\Http\Middleware\EnsureAdminDepartmentStorePermission;
use Modules\System\App\Http\Middleware\EnsureAdminMenuStorePermission;
use Modules\System\App\Http\Middleware\EnsureAdminPermission;
use Modules\System\App\Support\StorageConfigRegistrar;

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
        Route::aliasMiddleware('admin.permission.department.store', EnsureAdminDepartmentStorePermission::class);
        Route::aliasMiddleware('admin.permission.area.store', EnsureAdminAreaStorePermission::class);

        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'system');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'system');

        $this->app->booted(static function (): void {
            try {
                StorageConfigRegistrar::apply();
            } catch (\Throwable) {
                // 数据库未就绪时（如 migrate）忽略
            }
        });
    }
}
