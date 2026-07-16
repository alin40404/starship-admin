<?php

namespace Starship\Core\Contracts\Admin;

/**
 * 后台管理员查询契约。业务 Module 注入本接口，不直接依赖 System Admin 模型。
 * System 提供默认实现（Sanctum / admins 表）。
 */
interface AdminProviderInterface
{
    public function find(string|int $id): ?object;

    public function current(): ?object;
}
