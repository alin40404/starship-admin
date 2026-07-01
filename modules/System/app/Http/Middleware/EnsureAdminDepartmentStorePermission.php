<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\System\App\Http\Middleware\Concerns\ChecksAdminPermission;
use Modules\System\App\Services\AdminPermissionService;
use Modules\System\App\Support\DepartmentPermissions;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST departments：parent_id > 0 需 append，否则需 create。
 */
class EnsureAdminDepartmentStorePermission
{
    use ChecksAdminPermission;

    public function handle(Request $request, Closure $next): Response
    {
        $parentId = (int) $request->input('parent_id', 0);
        $permission = $parentId > 0 ? DepartmentPermissions::APPEND : DepartmentPermissions::CREATE;

        $denied = $this->denyUnless(
            $request,
            fn ($admin) => app(AdminPermissionService::class)->hasPermission($admin, $permission),
        );

        return $denied ?? $next($request);
    }
}
