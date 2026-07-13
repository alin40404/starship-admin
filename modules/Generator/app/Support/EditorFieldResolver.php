<?php

namespace Modules\Generator\App\Support;

final class EditorFieldResolver
{
    /** @var list<string> */
    protected const EDITOR_FIELD_NAMES = [
        'content', 'body', 'detail', 'description', 'intro', 'article', 'html', 'richtext', 'rich_text',
    ];

    public static function shouldUseEditor(string $columnName, string $columnType): bool
    {
        $name = strtolower($columnName);
        $type = strtolower($columnType);

        if (! preg_match('/\b(longtext|mediumtext)\b/', $type)) {
            return false;
        }

        if (in_array($name, self::EDITOR_FIELD_NAMES, true)) {
            return true;
        }

        foreach (self::EDITOR_FIELD_NAMES as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
