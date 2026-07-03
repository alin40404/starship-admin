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

    public const IMAGE = 'image';

    public const VIDEO = 'video';

    public const AUDIO = 'audio';

    public const DOCUMENT = 'document';

    public const RESOURCES = 'resources';

    /** @deprecated 已合并至 image */
    public const AVATAR = 'avatar';

    /** @deprecated 已由后端自动识别，归入 resources 或其它类型 */
    public const DEFAULT = 'default';

    /**
     * 根据 MIME / 扩展名自动识别附件分类。
     */
    public static function resolveFromFile(?string $mime, ?string $ext): string
    {
        $ext = strtolower(trim((string) $ext));
        $mime = strtolower(trim((string) $mime));

        if (
            str_starts_with($mime, 'image/')
            || in_array($ext, ['bmp', 'gif', 'ico', 'jpeg', 'jpg', 'png', 'svg', 'webp'], true)
        ) {
            return self::IMAGE;
        }

        if (
            str_starts_with($mime, 'video/')
            || in_array($ext, ['avi', 'mkv', 'mov', 'mp4', 'webm'], true)
        ) {
            return self::VIDEO;
        }

        if (
            str_starts_with($mime, 'audio/')
            || in_array($ext, ['aac', 'flac', 'm4a', 'mp3', 'ogg', 'wav'], true)
        ) {
            return self::AUDIO;
        }

        if (
            in_array($ext, ['csv', 'doc', 'docx', 'md', 'pdf', 'ppt', 'pptx', 'txt', 'xls', 'xlsx'], true)
            || str_contains($mime, 'pdf')
            || str_contains($mime, 'document')
            || str_contains($mime, 'word')
            || str_contains($mime, 'sheet')
            || str_contains($mime, 'excel')
            || str_contains($mime, 'presentation')
            || str_starts_with($mime, 'text/')
        ) {
            return self::DOCUMENT;
        }

        return self::RESOURCES;
    }

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

        if ($category === self::AVATAR) {
            return self::IMAGE;
        }

        if ($category === self::DEFAULT) {
            return self::RESOURCES;
        }

        return self::isValid($category) ? $category : null;
    }
}
