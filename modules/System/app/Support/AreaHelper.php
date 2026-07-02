<?php

namespace Modules\System\App\Support;

use Overtrue\Pinyin\Pinyin;

class AreaHelper
{
    public static function buildPinyin(?string $text): ?string
    {
        $value = trim((string) $text);
        if ($value === '') {
            return null;
        }

        return Pinyin::permalink($value, '');
    }

    public static function buildFirstLetter(?string $pinyin): ?string
    {
        $value = trim((string) $pinyin);
        if ($value === '') {
            return null;
        }

        return strtoupper(substr($value, 0, 1));
    }

    /**
     * @return array{pinyin: ?string, first: ?string}
     */
    public static function buildPinyinMeta(?string $text): array
    {
        $pinyin = self::buildPinyin($text);

        return [
            'pinyin' => $pinyin,
            'first' => self::buildFirstLetter($pinyin),
        ];
    }
}
