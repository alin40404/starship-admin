<?php

namespace Modules\System\App\Support;

/**
 * 部门管理（/org/departments）按钮权限，与前端 constants/department-permissions.ts 保持一致。
 */
final class DepartmentPermissions
{
    public const QUERY = 'system.departments.query';

    public const CREATE = 'system.departments.create';

    public const APPEND = 'system.departments.append';

    public const UPDATE = 'system.departments.update';

    public const DELETE = 'system.departments.delete';

    public const STATUS = 'system.departments.status';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增部门', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Append', 'title' => '新增下级', 'permission' => self::APPEND, 'sort' => 3],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 4],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 5],
            ['name' => 'Status', 'title' => '切换状态', 'permission' => self::STATUS, 'sort' => 6],
        ];
    }
}
