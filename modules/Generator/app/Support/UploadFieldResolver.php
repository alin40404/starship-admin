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

    public static function resolve(string $columnName, string $columnType): ?array
    {
        $name = strtolower($columnName);
        $type = strtolower($columnType);

        if (in_array($name, self::IMAGE_FIELD_NAMES, true)) {
            return ['kind' => 'image', 'component' => 'PictureUploadField', 'max_count' => str_ends_with($name, 's') ? 9 : 1];
        }

        if (in_array($name, self::FILE_FIELD_NAMES, true)) {
            $kind = in_array($name, ['brochure', 'manual'], true) ? 'file' : 'attachment';
            $component = $kind === 'file' ? 'Upload' : 'AttachmentField';

            return ['kind' => $kind, 'component' => $component, 'max_count' => str_ends_with($name, 's') ? 9 : 1];
        }

        if (str_contains($name, 'image') || str_contains($name, 'avatar') || str_contains($name, 'cover')) {
            return ['kind' => 'image', 'component' => 'PictureUploadField', 'max_count' => 1];
        }

        if (preg_match('/\b(file|attach)\b/', $name)) {
            return ['kind' => 'attachment', 'component' => 'AttachmentField', 'max_count' => 1];
        }

        if (preg_match('/\b(blob|binary)\b/', $type)) {
            return ['kind' => 'file', 'component' => 'Upload', 'max_count' => 1];
        }

        return null;
    }
}
