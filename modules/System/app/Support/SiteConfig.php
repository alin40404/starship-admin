<?php

namespace Modules\System\App\Support;

use Modules\System\App\Models\Setting;

/**
 * 站点对外展示配置（名称、Logo、备案号等）。
 */
final class SiteConfig
{
    public const KEY_NAME = 'site.name';

    public const KEY_LOGO = 'site.logo';

    public const KEY_BEIAN = 'site.beian';

    /** @var list<string> */
    private const PUBLIC_KEYS = [
        self::KEY_NAME,
        self::KEY_LOGO,
        self::KEY_BEIAN,
    ];

    /**
     * @return array{name: string, logo: string, beian: string}
     */
    public static function branding(): array
    {
        $values = Setting::query()
            ->whereIn('key', self::PUBLIC_KEYS)
            ->pluck('value', 'key');

        return [
            'name' => trim((string) ($values[self::KEY_NAME] ?? '')),
            'logo' => trim((string) ($values[self::KEY_LOGO] ?? '')),
            'beian' => trim((string) ($values[self::KEY_BEIAN] ?? '')),
        ];
    }
}
