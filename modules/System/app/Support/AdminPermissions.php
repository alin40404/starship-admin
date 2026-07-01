<?php

namespace Modules\System\App\Support;

/**
 * 管理员管理（/permission/admins）按钮权限，与前端 constants/admin-permissions.ts 保持一致。
 */
final class AdminPermissions
{
    public const QUERY = 'system.admins.query';

    public const CREATE = 'system.admins.create';

    public const UPDATE = 'system.admins.update';

    public const DELETE = 'system.admins.delete';

    public const BATCH_DELETE = 'system.admins.batch_delete';

    public const BATCH_ENABLE = 'system.admins.batch_enable';

    public const BATCH_DISABLE = 'system.admins.batch_disable';

    public const STATUS = 'system.admins.status';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 3],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 4],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 5],
            ['name' => 'BatchEnable', 'title' => '批量启用', 'permission' => self::BATCH_ENABLE, 'sort' => 6],
            ['name' => 'BatchDisable', 'title' => '批量禁用', 'permission' => self::BATCH_DISABLE, 'sort' => 7],
            ['name' => 'Status', 'title' => '切换状态', 'permission' => self::STATUS, 'sort' => 8],
        ];
    }
}
