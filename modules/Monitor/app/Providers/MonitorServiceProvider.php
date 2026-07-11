<?php

namespace Modules\Monitor\App\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Monitor\App\Http\Middleware\LogAdminOperation;
use Modules\Monitor\App\Services\ExceptionLogWriter;
use Throwable;

class MonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'monitor');

        Route::pushMiddlewareToGroup('api', LogAdminOperation::class);

        $this->app->afterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! method_exists($handler, 'reportable')) {
                return;
            }

            $handler->reportable(function (Throwable $exception): void {
                app(ExceptionLogWriter::class)->record($exception);
            });
        });
    }
}
