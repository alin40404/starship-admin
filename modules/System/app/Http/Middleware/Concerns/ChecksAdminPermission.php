<?php

namespace Modules\System\App\Http\Middleware\Concerns;

use Starship\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Admin;

trait ChecksAdminPermission
{
    protected function denyUnless(Request $request, callable $allowed): ?JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::error(__('core::api.unauthorized'), 401);
        }

        if (! $allowed($admin)) {
            return ApiResponse::error(__('core::api.forbidden'), 403);
        }

        return null;
    }
}
