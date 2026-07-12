<?php

namespace Modules\System\App\Support;

/**
 * 站点对外展示配置（名称、Logo、备案号等）。
 */
final class SiteConfig
{
    public const KEY_NAME = 'site.name';

    public const KEY_LOGO = 'site.logo';

    public const KEY_FAVICON = 'site.favicon';

    public const KEY_BEIAN = 'site.beian';

    public const KEY_FIXED_PAGE = 'site.fixed_page';

    public const KEY_TIMEZONE = 'site.timezone';

    public const DEFAULT_DISPLAY_TIMEZONE = 'Asia/Shanghai';

    /**
     * @return array{name: string, logo: string, favicon: string, beian: string, defaultHomePath: string, timezone: string}
     */
    public static function branding(): array
    {
        return [
            'name' => trim((string) SettingStore::get(self::KEY_NAME, '')),
            'logo' => trim((string) SettingStore::get(self::KEY_LOGO, '')),
            'favicon' => trim((string) (SettingStore::get(self::KEY_FAVICON, '') ?: SiteFaviconGenerator::DEFAULT_PUBLIC_PATH)),
            'beian' => trim((string) SettingStore::get(self::KEY_BEIAN, '')),
            'defaultHomePath' => self::normalizeHomePath((string) SettingStore::get(self::KEY_FIXED_PAGE, '')),
            'timezone' => self::displayTimezone(),
        ];
    }

    public static function displayTimezone(): string
    {
        $configured = trim((string) SettingStore::get(self::KEY_TIMEZONE, ''));
        if ($configured !== '' && in_array($configured, timezone_identifiers_list(), true)) {
            return $configured;
        }

        return self::DEFAULT_DISPLAY_TIMEZONE;
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
