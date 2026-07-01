<?php

namespace Modules\System\App\Support;

/**
 * 用户组管理（/org/user-groups）按钮权限，与前端 constants/user-group-permissions.ts 保持一致。
 */
final class UserGroupPermissions
{
    public const QUERY = 'system.user_groups.query';

    public const CREATE = 'system.user_groups.create';

    public const UPDATE = 'system.user_groups.update';

    public const DELETE = 'system.user_groups.delete';

    public const BATCH_DELETE = 'system.user_groups.batch_delete';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增用户组', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 3],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 4],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 5],
        ];
    }
}
