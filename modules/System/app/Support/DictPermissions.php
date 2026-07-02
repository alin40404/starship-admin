<?php

namespace Modules\System\App\Support;

/**
 * 字典管理（/platform/dicts）按钮权限，与前端 constants/dict-permissions.ts 保持一致。
 */
final class DictPermissions
{
    public const QUERY = 'system.dicts.query';

    public const CREATE = 'system.dicts.create';

    public const UPDATE = 'system.dicts.update';

    public const DELETE = 'system.dicts.delete';

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
