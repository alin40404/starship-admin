<?php

namespace Modules\System\App\Support;

/**
 * 系统配置（/platform/configs）按钮权限，与前端 constants/config-permissions.ts 保持一致。
 */
final class ConfigPermissions
{
    public const QUERY = 'system.configs.query';

    public const UPDATE = 'system.configs.update';

    public const DELETE = 'system.configs.delete';

    public const BATCH_DELETE = 'system.configs.batch_delete';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Update', 'title' => '保存配置', 'permission' => self::UPDATE, 'sort' => 2],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 3],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 4],
        ];
    }
}
