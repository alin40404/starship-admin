<?php

namespace Modules\Generator\App\Support;

/**
 * 将数据库字段注释转为前端展示标签。
 * 例如「状态:1启用,0禁用」→「状态」。
 */
final class ColumnLabelFormatter
{
    public static function fromComment(?string $comment, ?string $fallback = null): string
    {
        $text = trim((string) ($comment ?? ''));
        if ($text === '') {
            return self::fallbackLabel($fallback);
        }

        $parts = preg_split('/[:：]/u', $text, 2);
        $label = trim((string) ($parts[0] ?? $text));

        return $label !== '' ? $label : self::fallbackLabel($fallback);
    }

    protected static function fallbackLabel(?string $fallback): string
    {
        $fallback = trim((string) ($fallback ?? ''));

        return $fallback !== '' ? $fallback : '字段';
    }
}
