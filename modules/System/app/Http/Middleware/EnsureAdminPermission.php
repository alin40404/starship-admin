<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\System\App\Http\Middleware\Concerns\ChecksAdminPermission;
use Modules\System\App\Models\Admin;
use Modules\System\App\Services\AdminPermissionService;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    use ChecksAdminPermission;

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $denied = $this->denyUnless(
            $request,
            fn (Admin $admin) => app(AdminPermissionService::class)->hasPermission($admin, $permission),
        );

        return $denied ?? $next($request);
    }
}
