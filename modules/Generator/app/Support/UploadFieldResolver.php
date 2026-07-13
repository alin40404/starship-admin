<?php

namespace Modules\Generator\App\Support;

final class UploadFieldResolver
{
  /** @var list<string> */
    protected const IMAGE_FIELD_NAMES = [
        'image', 'images', 'avatar', 'cover', 'logo', 'thumb', 'thumbnail', 'banner', 'pic', 'picture',
    ];

    /** @var list<string> */
    protected const FILE_FIELD_NAMES = [
        'file', 'files', 'attachment', 'attachments', 'filepath', 'file_path', 'document', 'documents', 'brochure', 'manual',
    ];

    /**
     * @return array{
     *   kind: string,
     *   component: string,
     *   max_count?: int
     * }|null
     */
    public static function resolve(string $columnName, string $columnType, ?string $comment = null): ?array
    {
        $name = strtolower($columnName);
        $type = strtolower($columnType);
        $explicitMax = self::parseMaxCountFromComment($comment);

        if (in_array($name, self::IMAGE_FIELD_NAMES, true)) {
            return self::buildResult('image', 'PictureUploadField', $name, $explicitMax);
        }

        if (in_array($name, self::FILE_FIELD_NAMES, true)) {
            $kind = in_array($name, ['brochure', 'manual'], true) ? 'file' : 'attachment';
            $component = $kind === 'file' ? 'Upload' : 'AttachmentField';

            return self::buildResult($kind, $component, $name, $explicitMax);
        }

        if (str_contains($name, 'image') || str_contains($name, 'avatar') || str_contains($name, 'cover')) {
            return self::buildResult('image', 'PictureUploadField', $name, $explicitMax, defaultSingle: true);
        }

        if (preg_match('/\b(file|attach)\b/', $name)) {
            return self::buildResult('attachment', 'AttachmentField', $name, $explicitMax, defaultSingle: true);
        }

        if (preg_match('/\b(blob|binary)\b/', $type)) {
            return self::buildResult('file', 'Upload', $name, $explicitMax, defaultSingle: true);
        }

        return null;
    }

    public static function parseMaxCountFromComment(?string $comment): ?int
    {
        $comment = trim((string) ($comment ?? ''));
        if ($comment === '') {
            return null;
        }

        if (preg_match('/(?:最多|上限|max)[:：]\s*(\d+)/iu', $comment, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return null;
    }

    /**
     * @return array{kind: string, component: string, max_count?: int}
     */
    protected static function buildResult(
        string $kind,
        string $component,
        string $fieldName,
        ?int $explicitMax,
        bool $defaultSingle = false,
    ): array {
        $result = [
            'kind' => $kind,
            'component' => $component,
        ];

        $maxCount = $explicitMax ?? (self::isPluralFieldName($fieldName) && ! $defaultSingle ? null : 1);
        if ($maxCount !== null) {
            $result['max_count'] = $maxCount;
        }

        return $result;
    }

    protected static function isPluralFieldName(string $fieldName): bool
    {
        return str_ends_with(strtolower($fieldName), 's');
    }
}
