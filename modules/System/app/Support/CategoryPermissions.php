<?php

namespace Modules\System\App\Support;

/**
 * 分类管理（/platform/categories）按钮权限。
 */
final class CategoryPermissions
{
    public const QUERY = 'system.categories.query';

    public const CREATE = 'system.categories.create';

    public const UPDATE = 'system.categories.update';

    public const DELETE = 'system.categories.delete';

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
        ];
    }
}
