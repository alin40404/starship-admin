<?php

namespace Modules\System\App\Support;

/**
 * 演示文章（/org/demo-articles）按钮权限。
 */
final class DemoArticlePermissions
{
    public const QUERY = 'system.demo_articles.query';

    public const CREATE = 'system.demo_articles.create';

    public const UPDATE = 'system.demo_articles.update';

    public const DELETE = 'system.demo_articles.delete';
    public const BATCH_DELETE = 'system.demo_articles.batch_delete';

    public const BATCH_STATUS = 'system.demo_articles.batch_status';


    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增演示文章', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 3],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 4],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 5],

            ['name' => 'BatchStatus', 'title' => '批量改状态', 'permission' => self::BATCH_STATUS, 'sort' => 6],

        ];
    }
}
