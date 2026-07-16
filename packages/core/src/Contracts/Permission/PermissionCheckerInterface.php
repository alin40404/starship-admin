<?php

namespace Starship\Core\Contracts\Permission;

/**
 * 权限校验契约：业务侧只依赖接口，不耦合具体 RBAC 实现。
 * 接口已入库；System 绑定实现延后。
 */
interface PermissionCheckerInterface
{
    public function check(string $permission, string|int|null $userId = null): bool;
}
