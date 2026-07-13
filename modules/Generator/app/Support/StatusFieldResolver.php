<?php

namespace Modules\Generator\App\Support;

final class StatusFieldResolver
{
    /**
     * @return list<int|string>|null
     */
    public static function parseValuesFromComment(?string $comment): ?array
    {
        $comment = trim((string) ($comment ?? ''));
        if ($comment === '' || ! preg_match('/[:：]\s*(.+)$/u', $comment, $matches)) {
            return null;
        }

        $pairs = preg_split('/\s*,\s*/', trim($matches[1]));
        if (! is_array($pairs)) {
            return null;
        }

        $values = [];
        foreach ($pairs as $pair) {
            $pair = trim((string) $pair);
            if ($pair === '') {
                continue;
            }

            if (preg_match('/^(-?\d+)/', $pair, $match)) {
                $values[] = (int) $match[1];

                continue;
            }

            if (preg_match('/^([a-zA-Z_][\w-]*)/', $pair, $match)) {
                $values[] = $match[1];
            }
        }

        return $values === [] ? null : array_values(array_unique($values, SORT_REGULAR));
    }

    public static function fromColumnTypeAndComment(string $columnType, ?string $comment, ?array $validationRules = null): ?StatusFieldProfile
    {
        $type = strtolower($columnType);
        $isIntegerColumn = (bool) preg_match('/\b(int|tinyint|smallint|mediumint|bigint)\b/', $type);

        if ($isIntegerColumn) {
            $values = self::parseValuesFromComment($comment) ?? self::parseInRule($validationRules);
            if ($values !== null && count(array_filter($values, 'is_numeric')) === count($values)) {
                $enabled = (int) ($values[0] ?? 1);
                $disabled = (int) ($values[1] ?? 0);

                return new StatusFieldProfile('integer', $enabled, $disabled, array_map('intval', $values));
            }

            return StatusFieldProfile::integer();
        }

        $values = self::parseValuesFromComment($comment);
        if ($values === null) {
            $ruleValues = self::parseInRule($validationRules);
            if ($ruleValues !== null && count(array_filter($ruleValues, 'is_numeric')) !== count($ruleValues)) {
                $values = $ruleValues;
            }
        }

        /** @var list<string> $stringValues */
        $stringValues = array_map(
            static fn ($value): string => (string) $value,
            $values ?? ['normal', 'hidden'],
        );

        return StatusFieldProfile::string($stringValues);
    }

    public static function fromColumn(object $column): ?StatusFieldProfile
    {
        if (($column->column_name ?? '') !== 'status') {
            return null;
        }

        return self::fromColumnTypeAndComment(
            (string) $column->column_type,
            isset($column->raw_comment) ? (string) $column->raw_comment : (string) ($column->column_comment ?? null),
            is_array($column->validation_rules ?? null) ? $column->validation_rules : null,
        );
    }

    /**
     * @return list<int|string>|null
     */
    protected static function parseInRule(?array $rules): ?array
    {
        foreach ($rules ?? [] as $rule) {
            if (! is_string($rule) || ! str_starts_with($rule, 'in:')) {
                continue;
            }

            $raw = substr($rule, 3);

            return $raw === '' ? [] : explode(',', $raw);
        }

        return null;
    }
}
