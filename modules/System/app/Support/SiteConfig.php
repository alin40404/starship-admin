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

    public const KEY_FIXED_PAGE = 'site.fixed_page';

    /** @var list<string> */
    private const PUBLIC_KEYS = [
        self::KEY_NAME,
        self::KEY_LOGO,
        self::KEY_BEIAN,
        self::KEY_FIXED_PAGE,
    ];

    /**
     * @return array{name: string, logo: string, beian: string, defaultHomePath: string}
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
            'defaultHomePath' => self::normalizeHomePath((string) ($values[self::KEY_FIXED_PAGE] ?? '')),
        ];
    }

    private static function normalizeHomePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        return str_starts_with($trimmed, '/') ? $trimmed : '/'.$trimmed;
    }
}
