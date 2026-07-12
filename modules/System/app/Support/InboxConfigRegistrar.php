<?php

namespace Modules\System\App\Support;

use Modules\System\App\Models\InboxMessage;

/**
 * 从系统配置（站内信配置 Tab）读取站内信策略。
 */
final class InboxConfigRegistrar
{
    public const DEFAULT_PAGE_SIZE = 20;

    public const DEFAULT_RETENTION_DAYS = 90;

    public static function isEnabled(): bool
    {
        return SettingStore::get('inbox.enabled', '1') === '1';
    }

    public static function pageSize(): int
    {
        $size = (int) SettingStore::get('inbox.page_size', (string) self::DEFAULT_PAGE_SIZE);

        return $size > 0 ? $size : self::DEFAULT_PAGE_SIZE;
    }

    public static function retentionDays(): int
    {
        $days = (int) SettingStore::get('inbox.retention_days', (string) self::DEFAULT_RETENTION_DAYS);

        return max(0, $days);
    }

    public static function allowsType(string $type): bool
    {
        return match ($type) {
            InboxMessage::TYPE_SYSTEM => self::toBool(SettingStore::get('inbox.notify_system', '1')),
            InboxMessage::TYPE_TODO => self::toBool(SettingStore::get('inbox.notify_todo', '1')),
            InboxMessage::TYPE_ACCOUNT => self::toBool(SettingStore::get('inbox.notify_account', '1')),
            default => false,
        };
    }

    private static function toBool(?string $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
