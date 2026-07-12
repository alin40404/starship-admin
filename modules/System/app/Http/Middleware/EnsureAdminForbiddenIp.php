<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\System\App\Support\SiteForbiddenIp;
use Starship\Core\Support\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminForbiddenIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SiteForbiddenIp::isBlocked($request->ip())) {
            return ApiResponse::error(__('system::messages.forbidden_ip_blocked'), 403);
        }

        return $next($request);
    }
}
