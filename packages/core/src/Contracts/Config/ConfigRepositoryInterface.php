<?php

namespace Starship\Core\Contracts\Config;

/**
 * 平台配置仓储契约。业务 Module 注入本接口，不直接依赖 System SettingStore。
 * System 默认实现：ConfigRepository（configs 表）。
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
