<?php

namespace Modules\System\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\System\App\Models\Admin;
use Modules\System\App\Support\AdminRuntimeConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理端 Sanctum Token 空闲续期：鉴权成功后按 admin.jwt.ttl 延长 expires_at。
 */
class RenewAdminTokenExpiration
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('admin-api/*') || $response->getStatusCode() === 401) {
            return $response;
        }

        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return $response;
        }

        $token = $admin->currentAccessToken();
        if (! $token instanceof PersonalAccessToken || $token->expires_at === null) {
            return $response;
        }

        $token->forceFill([
            'expires_at' => now()->addMinutes(AdminRuntimeConfig::jwtTtlMinutes()),
        ])->save();

        return $response;
    }
}
