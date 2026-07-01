<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\System\App\Http\Middleware\Concerns\ChecksAdminPermission;
use Modules\System\App\Services\AdminPermissionService;
use Modules\System\App\Support\AdminPermissions;
use Symfony\Component\HttpFoundation\Response;

/**
 * PUT admins/batch-status：status=1 需 batch_enable，status=0 需 batch_disable。
 */
class EnsureAdminUserBatchStatusPermission
{
    use ChecksAdminPermission;

    public function handle(Request $request, Closure $next): Response
    {
        $status = (int) $request->input('status', -1);
        $permission = $status === 1
            ? AdminPermissions::BATCH_ENABLE
            : AdminPermissions::BATCH_DISABLE;

        $denied = $this->denyUnless(
            $request,
            fn ($admin) => app(AdminPermissionService::class)->hasPermission($admin, $permission),
        );

        return $denied ?? $next($request);
    }
}
