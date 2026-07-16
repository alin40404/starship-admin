<?php

namespace Modules\System\App\Support\Permission;

use Modules\System\App\Models\Admin;
use Modules\System\App\Services\AdminPermissionService;
use Starship\Core\Contracts\Permission\PermissionCheckerInterface;

/**
 * 后台权限校验：代理 AdminPermissionService，供业务 Module 注入使用。
 */
class PermissionChecker implements PermissionCheckerInterface
{
    public function __construct(
        protected AdminPermissionService $adminPermissionService,
    ) {}

    public function check(string $permission, string|int|null $userId = null): bool
    {
        $permission = trim($permission);
        if ($permission === '') {
            return false;
        }

        $admin = $this->resolveAdmin($userId);
        if ($admin === null) {
            return false;
        }

        return $this->adminPermissionService->hasPermission($admin, $permission);
    }

    private function resolveAdmin(string|int|null $userId): ?Admin
    {
        if ($userId !== null && $userId !== '') {
            $admin = Admin::query()->find($userId);

            return $admin instanceof Admin ? $admin : null;
        }

        $user = request()->user();

        return $user instanceof Admin ? $user : null;
    }
}
