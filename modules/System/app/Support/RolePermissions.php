<?php

namespace Modules\System\App\Support;

/**
 * 角色管理（/permission/roles）按钮权限，与前端 constants/role-permissions.ts 保持一致。
 */
final class RolePermissions
{
    public const QUERY = 'system.roles.query';

    public const CREATE = 'system.roles.create';

    public const UPDATE = 'system.roles.update';

    public const DELETE = 'system.roles.delete';

    public const BATCH_DELETE = 'system.roles.batch_delete';

    public const BATCH_STATUS = 'system.roles.batch_status';

    public const STATUS = 'system.roles.status';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增角色', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 3],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 4],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 5],
            ['name' => 'BatchStatus', 'title' => '批量改状态', 'permission' => self::BATCH_STATUS, 'sort' => 6],
            ['name' => 'Status', 'title' => '切换状态', 'permission' => self::STATUS, 'sort' => 7],
        ];
    }
}
