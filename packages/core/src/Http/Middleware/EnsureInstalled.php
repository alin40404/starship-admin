<?php

namespace Starship\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Starship\Core\Install\InstallLock;
use Starship\Core\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * 未安装时拦截业务 admin-api。
 * 安装接口与健康检查放行。
 */
class EnsureInstalled
{
    /** 与前端约定的业务码：未完成安装 */
    public const ERROR_CODE = 50301;

    /** @var list<string> */
    private const ALLOWED_PATHS = [
        'admin-api/install',
        'admin-api/install/*',
        'admin-api/ping',
    ];

    public function __construct(protected InstallLock $lock) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('admin-api', 'admin-api/*')) {
            return $next($request);
        }

        if ($this->isAllowedPath($request) || $this->lock->isInstalled()) {
            return $next($request);
        }

        return ApiResponse::error(
            '项目未安装，请先完成安装引导',
            503,
            self::ERROR_CODE,
            ['installed' => false],
        );
    }

    private function isAllowedPath(Request $request): bool
    {
        foreach (self::ALLOWED_PATHS as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }
}
