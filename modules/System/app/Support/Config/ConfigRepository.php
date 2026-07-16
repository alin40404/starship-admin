<?php

namespace Modules\System\App\Support\Config;

use Modules\System\App\Models\Setting;
use Modules\System\App\Support\SettingStore;
use Starship\Core\Contracts\Config\ConfigRepositoryInterface;

/**
 * 平台配置仓储：代理 SettingStore / configs 表，供业务 Module 注入使用。
 */
class ConfigRepository implements ConfigRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        $value = SettingStore::get($key, null);

        return $value !== null ? $value : $default;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'name' => $key,
                'group_name' => 'default',
                'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string'),
                'value' => $this->stringify($value),
                'sort' => 0,
            ],
        );

        SettingStore::forget();
    }

    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'group_name' => 'default',
                    'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string'),
                    'value' => $this->stringify($value),
                    'sort' => 0,
                ],
            );
        }

        SettingStore::forget();
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
