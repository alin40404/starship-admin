<?php

namespace Modules\System\App\Support;

/**
 * 栏目分类类型（字典 category_type）。
 */
final class CategoryType
{
    public const DICT_TYPE_CODE = 'category_type';

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

    public static function isValid(?string $type): bool
    {
        if ($type === null || $type === '') {
            return false;
        }

        return array_key_exists($type, self::labelMap());
    }
}
