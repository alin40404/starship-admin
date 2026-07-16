<?php

namespace Starship\Core\Contracts\Member;

/**
 * 前台会员查询契约。业务 Module 注入本接口，不直接依赖 Member 模型。
 * Member 模块提供默认实现（member guard / members 表）。
 */
interface MemberProviderInterface
{
    public function find(string|int $id): ?object;

    public function current(): ?object;
}
