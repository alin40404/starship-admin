<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\System\App\Http\Middleware\Concerns\ChecksAdminPermission;
use Modules\System\App\Services\AdminPermissionService;
use Modules\System\App\Support\AreaPermissions;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST areas：pid > 0 需 append，否则需 create。
 */
class EnsureAdminAreaStorePermission
{
    use ChecksAdminPermission;

    public function handle(Request $request, Closure $next): Response
    {
        $pid = (int) $request->input('pid', 0);
        $permission = $pid > 0 ? AreaPermissions::APPEND : AreaPermissions::CREATE;

        $denied = $this->denyUnless(
            $request,
            fn ($admin) => app(AdminPermissionService::class)->hasPermission($admin, $permission),
        );

        return $denied ?? $next($request);
    }
}
