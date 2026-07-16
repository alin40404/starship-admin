<?php

namespace Starship\Core\Contracts\Permission;

/**
 * 后台权限校验契约。业务 Module 注入本接口，不直接依赖 AdminPermissionService。
 * System 默认实现：PermissionChecker（RBAC + 超管）。
 * userId 为空时取当前请求登录管理员。
 */
interface PermissionCheckerInterface
{
    public function check(string $permission, string|int|null $userId = null): bool;
}
