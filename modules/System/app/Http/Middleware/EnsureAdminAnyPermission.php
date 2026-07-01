<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\System\App\Http\Middleware\Concerns\ChecksAdminPermission;
use Modules\System\App\Services\AdminPermissionService;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAnyPermission
{
    use ChecksAdminPermission;

    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $codes = array_values(array_filter(array_map('trim', explode(',', $permissions))));

        $denied = $this->denyUnless(
            $request,
            fn ($admin) => app(AdminPermissionService::class)->hasAnyPermission($admin, $codes),
        );

        return $denied ?? $next($request);
    }
}
