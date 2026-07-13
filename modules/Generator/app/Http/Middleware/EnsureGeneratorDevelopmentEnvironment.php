<?php

namespace Modules\Generator\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Starship\Core\Support\ApiResponse;
use Starship\Core\Support\DevEnvironment;
use Symfony\Component\HttpFoundation\Response;

class EnsureGeneratorDevelopmentEnvironment
{
    public function handle(Request $request, Closure $next): Response
    {
        if (DevEnvironment::isDevelopment()) {
            return $next($request);
        }

        return ApiResponse::error(__('generator::messages.dev_only'), 403);
    }
}
