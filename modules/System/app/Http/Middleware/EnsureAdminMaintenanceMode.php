<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\System\App\Models\Admin;
use Modules\System\App\Support\AdminRuntimeConfig;
use Starship\Core\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMaintenanceMode
{
    /** @var list<string> */
    private const PUBLIC_PATHS = [
        'admin-api/system/site',
        'admin-api/system/runtime',
        'admin-api/auth/login',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! AdminRuntimeConfig::maintenanceEnabled()) {
            return $next($request);
        }

        if ($this->isPublicPath($request)) {
            return $next($request);
        }

        $admin = $request->user();
        if ($admin instanceof Admin && (int) $admin->is_super === 1) {
            return $next($request);
        }

        return ApiResponse::error(__('system::messages.maintenance_mode'), 503);
    }

    private function isPublicPath(Request $request): bool
    {
        foreach (self::PUBLIC_PATHS as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }
}
