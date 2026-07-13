<?php

namespace Modules\Generator\App\Support;

final class BasicFieldResolver
{
    /** @var list<string> */
    protected const PASSWORD_FIELD_NAMES = [
        'password', 'passwd', 'secret', 'token', 'api_key', 'apikey',
    ];

    /** @var list<string> */
    protected const RATE_FIELD_NAMES = [
        'rate', 'score', 'rating', 'stars',
    ];

    /** @var list<string> */
    protected const ICON_FIELD_NAMES = [
        'icon',
    ];

    /** @var list<string> */
    protected const MENTIONS_FIELD_NAMES = [
        'keywords', 'mentions', 'mention',
    ];

    /**
     * @return array{
     *   form_component: string,
     *   dict_code: null,
     *   form_options: array<string, mixed>|null,
     *   validation_rules: list<string>
     * }|null
     */
    public static function resolve(string $name, string $type, ?string $comment, array $baseRules): ?array
    {
        $lowerName = strtolower($name);
        $lowerType = strtolower($type);

        if (in_array($lowerName, self::PASSWORD_FIELD_NAMES, true) || str_contains($lowerName, 'password')) {
            return [
                'form_component' => 'InputPassword',
                'dict_code' => null,
                'form_options' => ['kind' => 'password'],
                'validation_rules' => $baseRules,
            ];
        }

        if (self::isBooleanColumn($lowerName, $lowerType)) {
            return [
                'form_component' => 'Switch',
                'dict_code' => null,
                'form_options' => ['kind' => 'boolean'],
                'validation_rules' => array_merge($baseRules, ['boolean']),
            ];
        }

        if (preg_match('/\btime\b/', $lowerType) && ! str_contains($lowerType, 'datetime') && ! str_contains($lowerType, 'timestamp')) {
            return [
                'form_component' => 'TimePicker',
                'dict_code' => null,
                'form_options' => ['kind' => 'time', 'value_format' => 'HH:mm:ss'],
                'validation_rules' => $baseRules,
            ];
        }

        if (in_array($lowerName, self::RATE_FIELD_NAMES, true)
            || (preg_match('/\btinyint\b/', $lowerType) && str_contains((string) $comment, '评分'))) {
            return [
                'form_component' => 'Rate',
                'dict_code' => null,
                'form_options' => ['kind' => 'rate', 'count' => 5],
                'validation_rules' => array_merge($baseRules, ['integer', 'min:0', 'max:5']),
            ];
        }

        if (in_array($lowerName, self::ICON_FIELD_NAMES, true)) {
            return [
                'form_component' => 'IconPicker',
                'dict_code' => null,
                'form_options' => ['kind' => 'icon'],
                'validation_rules' => $baseRules,
            ];
        }

        $autoCompleteOptions = self::parseAutoCompleteOptions($comment);
        if ($autoCompleteOptions !== null) {
            return [
                'form_component' => 'AutoComplete',
                'dict_code' => null,
                'form_options' => [
                    'kind' => 'autocomplete',
                    'options' => $autoCompleteOptions,
                ],
                'validation_rules' => $baseRules,
            ];
        }

        if (in_array($lowerName, self::MENTIONS_FIELD_NAMES, true)
            || str_contains((string) $comment, '@')) {
            return [
                'form_component' => 'Mentions',
                'dict_code' => null,
                'form_options' => [
                    'kind' => 'mentions',
                    'options' => $autoCompleteOptions ?? ['@admin', '@system'],
                ],
                'validation_rules' => $baseRules,
            ];
        }

        return null;
    }

    protected static function isBooleanColumn(string $name, string $type): bool
    {
        if (preg_match('/\b(bool|boolean)\b/', $type)) {
            return $name !== 'status';
        }

        if (preg_match('/\btinyint\(1\)/', $type)) {
            return $name !== 'status';
        }

        return str_starts_with($name, 'is_') || str_starts_with($name, 'has_');
    }

    /**
     * @return list<string>|null
     */
    public static function parseAutoCompleteOptions(?string $comment): ?array
    {
        $comment = trim((string) ($comment ?? ''));
        if ($comment === '' || ! preg_match('/自动完成[:：]\s*(.+)$/u', $comment, $matches)) {
            return null;
        }

        $items = preg_split('/\s*,\s*/', trim($matches[1]));
        if (! is_array($items)) {
            return null;
        }

        $items = array_values(array_filter(array_map('trim', $items)));

        return $items === [] ? null : $items;
    }
}
