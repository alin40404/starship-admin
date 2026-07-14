<?php

namespace Starship\Core\Plugin;

/**
 * 插件类型目录：标识由后端统一维护，文案走 core::plugin 语言包。
 */
final class PluginTypeCatalog
{
    /**
     * 约定类型（展示顺序）。未知 type 仍可用，仅无内置译文时回退为原标识。
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            'payment',
            'shipping',
            'marketing',
            'sms',
            'email',
            'oauth',
            'storage',
            'ai',
            'theme',
            'analysis',
            'service',
            'feature',
            'other',
        ];
    }

    public static function label(?string $type): string
    {
        if ($type === null || trim($type) === '') {
            return (string) __('core::plugin.type_unknown');
        }

        $key = strtolower(trim($type));
        $langKey = 'core::plugin.types.'.$key;
        $translated = __($langKey);

        return $translated === $langKey ? $type : (string) $translated;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (string $key): array => [
                'value' => $key,
                'label' => self::label($key),
            ],
            self::keys(),
        );
    }
}
