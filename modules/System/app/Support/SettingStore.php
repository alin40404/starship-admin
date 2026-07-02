<?php

namespace Modules\System\App\Support;

use Modules\System\App\Models\Setting;

/**
 * 系统配置键值读取（请求内缓存）。
 */
final class SettingStore
{
    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();

        $value = self::$cache[$key] ?? null;

        return $value !== null && $value !== '' ? $value : $default;
    }

    public static function forget(): void
    {
        self::$cache = null;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = Setting::query()->pluck('value', 'key')->all();
    }
}
