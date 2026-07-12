<?php

namespace Modules\System\App\Support;

/**
 * 管理端临时封禁 IP（配置 site.forbidden_ip，一行一条）。
 */
final class SiteForbiddenIp
{
    public const SETTING_KEY = 'site.forbidden_ip';

    public static function isBlocked(?string $ip): bool
    {
        $normalized = trim((string) $ip);
        if ($normalized === '') {
            return false;
        }

        foreach (self::blockedIps() as $blocked) {
            if ($blocked === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function blockedIps(): array
    {
        $raw = (string) SettingStore::get(self::SETTING_KEY, '');
        if (trim($raw) === '') {
            return [];
        }

        $ips = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $ips[] = $line;
        }

        return $ips;
    }
}
