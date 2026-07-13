<?php

namespace Modules\System\App\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\System\App\Http\Middleware\EnsureAdminAnyPermission;
use Modules\System\App\Http\Middleware\EnsureAdminAreaStorePermission;
use Modules\System\App\Http\Middleware\EnsureAdminDepartmentStorePermission;
use Modules\System\App\Http\Middleware\EnsureAdminForbiddenIp;
use Modules\System\App\Http\Middleware\EnsureAdminMaintenanceMode;
use Modules\System\App\Http\Middleware\EnsureAdminMenuStorePermission;
use Modules\System\App\Http\Middleware\EnsureAdminPermission;
use Modules\System\App\Http\Middleware\LogAdminOperation;
use Modules\System\App\Http\Middleware\RenewAdminTokenExpiration;
use Modules\System\App\Services\ExceptionLogWriter;
use Modules\System\App\Support\MailConfigRegistrar;
use Modules\System\App\Support\Sms\SmsGateway;
use Modules\System\App\Support\StorageConfigRegistrar;
use Starship\Core\Contracts\Sms\SmsSenderInterface;
use Throwable;

class SystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsSenderInterface::class, SmsGateway::class);
    }

    public function boot(): void
    {
        Route::aliasMiddleware('admin.permission', EnsureAdminPermission::class);
        Route::aliasMiddleware('admin.permission.any', EnsureAdminAnyPermission::class);
        Route::aliasMiddleware('admin.permission.menu.store', EnsureAdminMenuStorePermission::class);
        Route::aliasMiddleware('admin.permission.department.store', EnsureAdminDepartmentStorePermission::class);
        Route::aliasMiddleware('admin.permission.area.store', EnsureAdminAreaStorePermission::class);
        Route::aliasMiddleware('admin.maintenance', EnsureAdminMaintenanceMode::class);
        Route::aliasMiddleware('admin.forbidden_ip', EnsureAdminForbiddenIp::class);

        Route::pushMiddlewareToGroup('api', RenewAdminTokenExpiration::class);
        Route::pushMiddlewareToGroup('api', LogAdminOperation::class);

        $this->app->afterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! method_exists($handler, 'reportable')) {
                return;
            }

            $handler->reportable(function (Throwable $exception): void {
                app(ExceptionLogWriter::class)->record($exception);
            });
        });

        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'system');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'system');

        $this->app->booted(static function (): void {
            try {
                StorageConfigRegistrar::apply();
                MailConfigRegistrar::apply();
            } catch (\Throwable) {
                // 数据库未就绪时（如 migrate）忽略
            }
        });
    }
}
