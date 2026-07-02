<?php

namespace Modules\System\App\Support;

/**
 * 分类管理（/platform/categories）按钮权限，与前端 constants/category-permissions.ts 保持一致。
 */
final class CategoryPermissions
{
    public const QUERY = 'system.categories.query';

    public const CREATE = 'system.categories.create';

    public const APPEND = 'system.categories.append';

    public const UPDATE = 'system.categories.update';

    public const DELETE = 'system.categories.delete';

    public const BATCH_DELETE = 'system.categories.batch_delete';

    public const BATCH_STATUS = 'system.categories.batch_status';

    public const STATUS = 'system.categories.status';

    public const SORT = 'system.categories.sort';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Append', 'title' => '新增下级', 'permission' => self::APPEND, 'sort' => 3],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 4],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 5],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 6],
            ['name' => 'BatchStatus', 'title' => '批量改状态', 'permission' => self::BATCH_STATUS, 'sort' => 7],
            ['name' => 'Status', 'title' => '切换状态', 'permission' => self::STATUS, 'sort' => 8],
            ['name' => 'Sort', 'title' => '调整排序', 'permission' => self::SORT, 'sort' => 9],
        ];
    }
}
