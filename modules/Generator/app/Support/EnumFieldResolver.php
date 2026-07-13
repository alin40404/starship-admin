<?php

namespace Modules\Generator\App\Support;

final class EnumFieldResolver
{
    /**
     * @return list<string>|null
     */
    public static function parseEnumColumnType(string $columnType): ?array
    {
        if (! preg_match("/\benum\((.+)\)/i", $columnType, $matches)) {
            return null;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $matches[1], $valueMatches);

        $values = array_map(
            static fn (string $value): string => str_replace(["\\'", '\\\\'], ["'", '\\'], $value),
            $valueMatches[1] ?? [],
        );

        return $values === [] ? null : array_values($values);
    }

    /**
     * @return list<array{label: string, value: string|int}>|null
     */
    public static function parseOptionsFromComment(?string $comment): ?array
    {
        $comment = trim((string) ($comment ?? ''));
        if ($comment === '' || ! preg_match('/[:：]\s*(.+)$/u', $comment, $matches)) {
            return null;
        }

        $pairs = preg_split('/\s*,\s*/', trim($matches[1]));
        if (! is_array($pairs)) {
            return null;
        }

        $options = [];
        foreach ($pairs as $pair) {
            $pair = trim((string) $pair);
            if ($pair === '') {
                continue;
            }

            if (preg_match('/^(-?\d+)\s*(.*)$/u', $pair, $match)) {
                $value = (int) $match[1];
                $label = trim($match[2]) !== '' ? trim($match[2]) : (string) $value;
                $options[] = ['label' => $label, 'value' => $value];

                continue;
            }

            if (preg_match('/^([a-zA-Z_][\w-]*)\s*[=:：]?\s*(.*)$/u', $pair, $match)) {
                $value = $match[1];
                $label = trim($match[2]) !== '' ? trim($match[2]) : $value;
                $options[] = ['label' => $label, 'value' => $value];
            }
        }

        return $options === [] ? null : $options;
    }

    /**
     * @param  list<string>  $values
     * @return list<array{label: string, value: string}>
     */
    public static function valuesToOptions(array $values): array
    {
        return array_map(
            static fn (string $value): array => ['label' => $value, 'value' => $value],
            $values,
        );
    }

    public static function parseDictCodeFromComment(?string $comment): ?string
    {
        $comment = trim((string) ($comment ?? ''));
        if ($comment === '') {
            return null;
        }

        if (preg_match('/字典[:：]\s*([a-zA-Z_][\w-]*)/u', $comment, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
