<?php

namespace Starship\Core\Contracts\Config;

/**
 * 配置仓储契约（平台级配置读写）。
 * 接口已入库；System 绑定实现延后。
 */
interface ConfigRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void;
}
