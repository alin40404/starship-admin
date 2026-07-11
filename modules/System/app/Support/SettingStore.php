<?php

namespace Modules\System\App\Support;

use Illuminate\Support\Facades\Cache;
use Modules\System\App\Models\Setting;

/**
 * 系统配置键值读取（Laravel 缓存 + 请求内缓存）。
 */
final class SettingStore
{
    private const CACHE_KEY = 'system:settings:all';

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
        Cache::forget(self::CACHE_KEY);
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = Cache::rememberForever(self::CACHE_KEY, static function (): array {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }
}
