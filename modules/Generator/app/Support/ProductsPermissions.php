<?php

namespace Modules\Generator\App\Support;

/**
 * Products（/generator/products）按钮权限。
 */
final class ProductsPermissions
{
    public const QUERY = 'generator.products.query';

    public const CREATE = 'generator.products.create';

    public const UPDATE = 'generator.products.update';

    public const DELETE = 'generator.products.delete';
    public const BATCH_DELETE = 'generator.products.batch_delete';

    public const BATCH_STATUS = 'generator.products.batch_status';


    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增Products', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 3],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 4],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 5],

            ['name' => 'BatchStatus', 'title' => '批量改状态', 'permission' => self::BATCH_STATUS, 'sort' => 6],

        ];
    }
}
