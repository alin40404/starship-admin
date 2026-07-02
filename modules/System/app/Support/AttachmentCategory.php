<?php

namespace Modules\System\App\Support;

use Modules\System\App\Models\DictItem;
use Modules\System\App\Models\DictType;

/**
 * 附件分类（字典配置）。
 */
final class AttachmentCategory
{
    public const DICT_TYPE_CODE = 'attachment_category';

    /**
     * @return list<array{value: string, label: string, sort: int}>
     */
    public static function options(): array
    {
        $type = DictType::query()
            ->where('code', self::DICT_TYPE_CODE)
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

    /**
     * @return array<string, string>
     */
    public static function labelMap(): array
    {
        $map = [];
        foreach (self::options() as $item) {
            $map[$item['value']] = $item['label'];
        }

        return $map;
    }

    public static function label(?string $category): ?string
    {
        if ($category === null || $category === '') {
            return null;
        }

        return self::labelMap()[$category] ?? null;
    }

    public static function isValid(?string $category): bool
    {
        if ($category === null || $category === '') {
            return true;
        }

        return array_key_exists($category, self::labelMap());
    }

    public static function normalize(?string $category): ?string
    {
        if ($category === null || $category === '') {
            return null;
        }

        return self::isValid($category) ? $category : null;
    }
}
