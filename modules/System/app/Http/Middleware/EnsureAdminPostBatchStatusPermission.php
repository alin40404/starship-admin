<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\System\App\Http\Middleware\Concerns\ChecksAdminPermission;
use Modules\System\App\Services\AdminPermissionService;
use Modules\System\App\Support\PostPermissions;
use Symfony\Component\HttpFoundation\Response;

/**
 * PUT posts/batch-status：status=1 需 batch_enable，status=0 需 batch_disable。
 */
class EnsureAdminPostBatchStatusPermission
{
    use ChecksAdminPermission;

    public function handle(Request $request, Closure $next): Response
    {
        $status = (int) $request->input('status', -1);
        $permission = $status === 1
            ? PostPermissions::BATCH_ENABLE
            : PostPermissions::BATCH_DISABLE;

        $denied = $this->denyUnless(
            $request,
            fn ($admin) => app(AdminPermissionService::class)->hasPermission($admin, $permission),
        );

        return $denied ?? $next($request);
    }
}
