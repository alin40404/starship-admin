<?php

namespace Modules\System\App\Support;

/**
 * 从系统配置（邮件配置 Tab）注册 Laravel SMTP 发信参数。
 */
final class MailConfigRegistrar
{
    private static bool $registered = false;

    public static function reset(): void
    {
        self::$registered = false;
    }

    public static function apply(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        if (! self::isConfigured()) {
            return;
        }

        $host = trim((string) SettingStore::get('mail.smtp_host', ''));
        $port = (int) SettingStore::get('mail.smtp_port', '465');
        $user = trim((string) SettingStore::get('mail.smtp_user', ''));
        $pass = trim((string) SettingStore::get('mail.smtp_pass', ''));
        $verifyType = trim((string) SettingStore::get('mail.verify_type', '2'));
        $from = trim((string) SettingStore::get('mail.from', ''));
        if ($from === '') {
            $from = $user;
        }

        $fromName = trim((string) SettingStore::get(SiteConfig::KEY_NAME, ''));
        if ($fromName === '') {
            $fromName = (string) config('app.name', 'Starship Admin');
        }

        $scheme = match ($verifyType) {
            '2' => 'smtps',
            default => 'smtp',
        };

        if ($port <= 0) {
            $port = $scheme === 'smtps' ? 465 : 25;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $user,
            'mail.mailers.smtp.password' => $pass,
            'mail.from.address' => $from,
            'mail.from.name' => $fromName,
        ]);
    }

    public static function isConfigured(): bool
    {
        $host = trim((string) SettingStore::get('mail.smtp_host', ''));
        $user = trim((string) SettingStore::get('mail.smtp_user', ''));
        $pass = trim((string) SettingStore::get('mail.smtp_pass', ''));

        return $host !== '' && $user !== '' && $pass !== '';
    }
}
