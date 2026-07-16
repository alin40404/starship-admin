<?php

namespace Modules\System\App\Providers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
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
use Modules\System\App\Http\Middleware\SetLocale;
use Modules\System\App\Services\ExceptionLogWriter;
use Modules\System\App\Support\GuestRedirect;
use Modules\System\App\Support\Mail\MailGateway;
use Modules\System\App\Support\MailConfigRegistrar;
use Modules\System\App\Support\Sms\SmsGateway;
use Modules\System\App\Support\Storage\LocalStorage;
use Modules\System\App\Support\StorageConfigRegistrar;
use Modules\System\App\Support\Payment\PaymentGateway;
use Modules\System\App\Support\AdminProvider;
use Modules\System\App\Support\Config\ConfigRepository;
use Modules\System\App\Support\Permission\PermissionChecker;
use Starship\Core\Contracts\Mail\MailSenderInterface;
use Starship\Core\Contracts\Payment\PaymentInterface;
use Starship\Core\Contracts\Sms\SmsSenderInterface;
use Starship\Core\Contracts\Storage\StorageInterface;
use Starship\Core\Contracts\Admin\AdminProviderInterface;
use Starship\Core\Contracts\Config\ConfigRepositoryInterface;
use Starship\Core\Contracts\Permission\PermissionCheckerInterface;
use Starship\Core\Support\ApiResponse;
use Throwable;

class SystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/system.php', 'system');

        $this->app->singleton(SmsSenderInterface::class, SmsGateway::class);
        $this->app->singleton(MailSenderInterface::class, MailGateway::class);
        $this->app->singleton(LocalStorage::class);
        $this->app->singleton(StorageInterface::class, fn ($app) => $app->make(LocalStorage::class));
        $this->app->singleton(AdminProviderInterface::class, AdminProvider::class);
        $this->app->singleton(PaymentInterface::class, PaymentGateway::class);
        $this->app->singleton(ConfigRepositoryInterface::class, ConfigRepository::class);
        $this->app->singleton(PermissionCheckerInterface::class, PermissionChecker::class);
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

        // api：admin-api 只认 Accept-Language；web：cookie/session（见 SetLocale）
        Route::prependMiddlewareToGroup('api', SetLocale::class);
        Route::pushMiddlewareToGroup('web', SetLocale::class);

        Route::pushMiddlewareToGroup('api', RenewAdminTokenExpiration::class);
        Route::pushMiddlewareToGroup('api', LogAdminOperation::class);

        // admin-api：未登录不跳转 + JSON 401
        GuestRedirect::using(static function (Request $request): string|null|false {
            if ($request->is('admin-api', 'admin-api/*')) {
                return null;
            }

            return false;
        });

        $this->app->afterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (method_exists($handler, 'reportable')) {
                $handler->reportable(function (Throwable $exception): void {
                    app(ExceptionLogWriter::class)->record($exception);
                });
            }

            if (method_exists($handler, 'renderable')) {
                $handler->renderable(function (AuthenticationException $e, Request $request) {
                    if ($request->is('admin-api', 'admin-api/*')) {
                        return ApiResponse::error(__('core::api.session_expired'), 401);
                    }
                });
            }
        });

        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'system');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'system');

        // 全局校验 / auth 语言包（validation.*、auth.*），与 system:: 业务文案同目录
        $langPath = __DIR__.'/../../resources/lang';
        $this->callAfterResolving('translator', function ($translator) use ($langPath): void {
            $loader = $translator->getLoader();
            if (method_exists($loader, 'addPath')) {
                $loader->addPath($langPath);
            }
        });

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
