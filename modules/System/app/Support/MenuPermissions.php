<?php

namespace Modules\System\App\Support;

/**
 * 菜单管理（/permission/menus）按钮权限，与前端 constants/menu-permissions.ts 保持一致。
 */
final class MenuPermissions
{
    public const QUERY = 'system.menus.query';

    public const CREATE = 'system.menus.create';

    public const APPEND = 'system.menus.append';

    public const UPDATE = 'system.menus.update';

    public const DELETE = 'system.menus.delete';

    public const BATCH_DELETE = 'system.menus.batch_delete';

    public const BATCH_ENABLE = 'system.menus.batch_enable';

    public const BATCH_DISABLE = 'system.menus.batch_disable';

    public const STATUS = 'system.menus.status';

    public const SORT = 'system.menus.sort';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增菜单', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Append', 'title' => '新增下级', 'permission' => self::APPEND, 'sort' => 3],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 4],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 5],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 6],
            ['name' => 'BatchEnable', 'title' => '批量启用', 'permission' => self::BATCH_ENABLE, 'sort' => 7],
            ['name' => 'BatchDisable', 'title' => '批量禁用', 'permission' => self::BATCH_DISABLE, 'sort' => 8],
            ['name' => 'Status', 'title' => '切换状态', 'permission' => self::STATUS, 'sort' => 9],
            ['name' => 'Sort', 'title' => '调整排序', 'permission' => self::SORT, 'sort' => 10],
        ];
    }
}
