<?php

namespace Modules\System\App\Http\Middleware\Concerns;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\System\App\Models\Admin;

trait ChecksAdminPermission
{
    protected function denyUnless(Request $request, callable $allowed): ?JsonResponse
    {
        $admin = $request->user();
        if (! $admin instanceof Admin) {
            return ApiResponse::error('未登录', 401);
        }

        if (! $allowed($admin)) {
            return ApiResponse::error('无权限执行此操作', 403);
        }

        return null;
    }
}
