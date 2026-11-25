<?php

namespace Starship\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Starship\Core\Install\InstallLock;
use Starship\Core\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotInstalled
{
    public function __construct(protected InstallLock $lock) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->lock->isInstalled()) {
            return ApiResponse::error('项目已安装，安装引导已关闭', 403);
        }

        return $next($request);
    }
}
