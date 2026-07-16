<?php

namespace Modules\Cms\App\Support;

final class CmsPermissions
{
    public const CATEGORIES_QUERY = 'cms.categories.query';

    public const CATEGORIES_CREATE = 'cms.categories.create';

    public const CATEGORIES_APPEND = 'cms.categories.append';

    public const CATEGORIES_UPDATE = 'cms.categories.update';

    public const CATEGORIES_DELETE = 'cms.categories.delete';

    public const CATEGORIES_BATCH_DELETE = 'cms.categories.batch_delete';

    public const CATEGORIES_BATCH_STATUS = 'cms.categories.batch_status';

    public const CATEGORIES_STATUS = 'cms.categories.status';

    public const CATEGORIES_SORT = 'cms.categories.sort';

    public const ARTICLES_QUERY = 'cms.articles.query';

    public const ARTICLES_CREATE = 'cms.articles.create';

    public const ARTICLES_UPDATE = 'cms.articles.update';

    public const ARTICLES_STATUS = 'cms.articles.status';

    public const ARTICLES_DELETE = 'cms.articles.delete';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function categoryButtonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::CATEGORIES_QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增', 'permission' => self::CATEGORIES_CREATE, 'sort' => 2],
            ['name' => 'Append', 'title' => '新增下级', 'permission' => self::CATEGORIES_APPEND, 'sort' => 3],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::CATEGORIES_UPDATE, 'sort' => 4],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::CATEGORIES_DELETE, 'sort' => 5],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::CATEGORIES_BATCH_DELETE, 'sort' => 6],
            ['name' => 'BatchStatus', 'title' => '批量改状态', 'permission' => self::CATEGORIES_BATCH_STATUS, 'sort' => 7],
            ['name' => 'Status', 'title' => '切换状态', 'permission' => self::CATEGORIES_STATUS, 'sort' => 8],
            ['name' => 'Sort', 'title' => '调整排序', 'permission' => self::CATEGORIES_SORT, 'sort' => 9],
        ];
    }

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function articleButtonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::ARTICLES_QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增文章', 'permission' => self::ARTICLES_CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::ARTICLES_UPDATE, 'sort' => 3],
            ['name' => 'Status', 'title' => '发布/草稿', 'permission' => self::ARTICLES_STATUS, 'sort' => 4],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::ARTICLES_DELETE, 'sort' => 5],
        ];
    }
}
