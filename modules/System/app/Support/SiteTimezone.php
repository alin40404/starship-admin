<?php

namespace Modules\System\App\Support;

/**
 * 站点展示时区（字典 site_timezone）。
 */
final class SiteTimezone
{
    public const DICT_TYPE_CODE = 'site_timezone';

    /**
     * @return list<array{value: string, label: string, sort: int}>
     */
    public static function options(): array
    {
        return DictStore::options(self::DICT_TYPE_CODE);
    }

    /**
     * @return array<string, string>
     */
    public static function labelMap(): array
    {
        return DictStore::labelMap(self::DICT_TYPE_CODE);
    }

    public static function isValid(?string $timezone): bool
    {
        if ($timezone === null || $timezone === '') {
            return false;
        }

        return array_key_exists($timezone, self::labelMap());
    }
}
