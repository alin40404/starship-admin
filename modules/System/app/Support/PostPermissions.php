<?php

namespace Modules\System\App\Support;

/**
 * 岗位管理（/org/posts）按钮权限，与前端 constants/post-permissions.ts 保持一致。
 */
final class PostPermissions
{
    public const QUERY = 'system.posts.query';

    public const CREATE = 'system.posts.create';

    public const UPDATE = 'system.posts.update';

    public const DELETE = 'system.posts.delete';

    public const BATCH_DELETE = 'system.posts.batch_delete';

    public const BATCH_STATUS = 'system.posts.batch_status';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增岗位', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 3],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 4],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 5],
            ['name' => 'BatchStatus', 'title' => '批量改状态', 'permission' => self::BATCH_STATUS, 'sort' => 6],
        ];
    }
}
