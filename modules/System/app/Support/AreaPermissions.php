<?php

namespace Modules\System\App\Support;

/**
 * 区域管理（/platform/regions）按钮权限，与前端 constants/region-permissions.ts 保持一致。
 */
final class AreaPermissions
{
    public const QUERY = 'system.regions.query';

    public const CREATE = 'system.regions.create';

    public const APPEND = 'system.regions.append';

    public const UPDATE = 'system.regions.update';

    public const DELETE = 'system.regions.delete';

    public const BATCH_DELETE = 'system.regions.batch_delete';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增区域', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Append', 'title' => '新增下级', 'permission' => self::APPEND, 'sort' => 3],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 4],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 5],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 6],
        ];
    }
}
