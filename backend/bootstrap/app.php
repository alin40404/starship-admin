<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Starship\Core\Http\Middleware\SetLocale;
use Starship\Core\Support\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('api', SetLocale::class);
        // web 组末尾挂载，确保 Cookie / Session 已就绪
        $middleware->appendToGroup('web', SetLocale::class);

        $middleware->redirectGuestsTo(function (Request $request) {
            // admin-api：纯 JSON 接口，未登录返回 401，不重定向
            if ($request->is('admin-api/*')) {
                return null;
            }

            // 会员前台：未登录跳转会员登录页
            if ($request->is('member', 'member/*')) {
                return route('member.login');
            }

            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('admin-api/*')) {
                return ApiResponse::error(__('core::api.session_expired'), 401);
            }
        });
    })->create();
