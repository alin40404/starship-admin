<?php

namespace Modules\System\App\Support;

/**
 * 模块管理（/dev/modules）按钮权限，与前端 constants/module-permissions.ts 保持一致。
 */
final class ModulePermissions
{
    public const QUERY = 'system.modules.query';

    public const UPDATE = 'system.modules.update';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Update', 'title' => '启用/禁用', 'permission' => self::UPDATE, 'sort' => 2],
        ];
    }
}
