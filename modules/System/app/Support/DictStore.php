<?php

namespace Modules\System\App\Support;

use Illuminate\Support\Facades\Cache;
use Modules\System\App\Models\DictItem;
use Modules\System\App\Models\DictType;

/**
 * 字典配置读取（Laravel 缓存 + 请求内缓存）。
 */
final class DictStore
{
    private const CACHE_PREFIX = 'system:dict:options:';

    /** @var array<string, list<array{value: string, label: string, sort: int}>> */
    private static array $requestCache = [];

    /**
     * @return list<array{value: string, label: string, sort: int}>
     */
    public static function options(string $typeCode): array
    {
        if (isset(self::$requestCache[$typeCode])) {
            return self::$requestCache[$typeCode];
        }

        $options = Cache::rememberForever(self::cacheKey($typeCode), function () use ($typeCode) {
            return self::loadOptionsFromDatabase($typeCode);
        });

        self::$requestCache[$typeCode] = $options;

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function labelMap(string $typeCode): array
    {
        $map = [];
        foreach (self::options($typeCode) as $item) {
            $map[$item['value']] = $item['label'];
        }

        return $map;
    }

    public static function forget(string $typeCode): void
    {
        unset(self::$requestCache[$typeCode]);
        Cache::forget(self::cacheKey($typeCode));
    }

    public static function forgetByTypeId(?int $typeId): void
    {
        if ($typeId === null) {
            return;
        }

        $code = DictType::query()->whereKey($typeId)->value('code');
        if (is_string($code) && $code !== '') {
            self::forget($code);
        }
    }

    private static function cacheKey(string $typeCode): string
    {
        return self::CACHE_PREFIX.$typeCode;
    }

    /**
     * @return list<array{value: string, label: string, sort: int}>
     */
    private static function loadOptionsFromDatabase(string $typeCode): array
    {
        $type = DictType::query()
            ->where('code', $typeCode)
            ->where('status', 1)
            ->first();

        if (! $type) {
            return [];
        }

        return DictItem::query()
            ->where('dict_type_id', $type->id)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['value', 'label', 'sort'])
            ->map(fn ($item) => [
                'value' => (string) $item->value,
                'label' => (string) $item->label,
                'sort' => (int) $item->sort,
            ])
            ->all();
    }
}
