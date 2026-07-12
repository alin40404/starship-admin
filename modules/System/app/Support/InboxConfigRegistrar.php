<?php

namespace Modules\System\App\Support;

use Modules\System\App\Models\InboxMessage;

/**
 * 从系统配置（站内信配置 Tab）读取站内信策略。
 */
final class InboxConfigRegistrar
{
    public const DEFAULT_PAGE_SIZE = 20;

    public const DEFAULT_RETENTION_DAYS = 0;

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

    /**
     * @return list<string>
     */
    public static function allCategories(): array
    {
        return [
            InboxMessage::CATEGORY_NOTICE,
            InboxMessage::CATEGORY_APPROVAL,
            InboxMessage::CATEGORY_TASK,
            InboxMessage::CATEGORY_CRM,
            InboxMessage::CATEGORY_ALERT,
        ];
    }

    public static function isCategoryVisible(string $category): bool
    {
        return self::toBool(SettingStore::get(self::categorySettingKey($category), '1'));
    }

    /**
     * @return list<string>
     */
    public static function visibleCategories(): array
    {
        return array_values(array_filter(
            self::allCategories(),
            static fn (string $category): bool => self::isCategoryVisible($category),
        ));
    }

    public static function categorySettingKey(string $category): string
    {
        return 'inbox.category_'.$category;
    }

    private static function toBool(?string $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
