<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Starship\Core\Http\Middleware\EnsureInstalled;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 未安装时拦截业务 admin-api（安装接口 / ping 由中间件内部放行）
        $middleware->appendToGroup('api', EnsureInstalled::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
