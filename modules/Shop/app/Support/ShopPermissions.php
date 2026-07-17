<?php

namespace Modules\Shop\App\Support;

final class ShopPermissions
{
    public const CATEGORIES_QUERY = 'shop.categories.query';

    public const CATEGORIES_CREATE = 'shop.categories.create';

    public const CATEGORIES_APPEND = 'shop.categories.append';

    public const CATEGORIES_UPDATE = 'shop.categories.update';

    public const CATEGORIES_DELETE = 'shop.categories.delete';

    public const CATEGORIES_BATCH_DELETE = 'shop.categories.batch_delete';

    public const CATEGORIES_BATCH_STATUS = 'shop.categories.batch_status';

    public const CATEGORIES_STATUS = 'shop.categories.status';

    public const CATEGORIES_SORT = 'shop.categories.sort';

    public const BRANDS_QUERY = 'shop.brands.query';

    public const BRANDS_CREATE = 'shop.brands.create';

    public const BRANDS_UPDATE = 'shop.brands.update';

    public const BRANDS_DELETE = 'shop.brands.delete';

    public const PRODUCTS_QUERY = 'shop.products.query';

    public const PRODUCTS_CREATE = 'shop.products.create';

    public const PRODUCTS_UPDATE = 'shop.products.update';

    public const PRODUCTS_STATUS = 'shop.products.status';

    public const PRODUCTS_DELETE = 'shop.products.delete';

    public const ORDERS_QUERY = 'shop.orders.query';

    public const ORDERS_CREATE = 'shop.orders.create';

    public const ORDERS_PREPAY = 'shop.orders.prepay';

    public const ORDERS_CANCEL = 'shop.orders.cancel';

    public const ORDERS_REFUND = 'shop.orders.refund';

    public const ORDERS_DELETE = 'shop.orders.delete';

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
    public static function brandButtonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::BRANDS_QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增品牌', 'permission' => self::BRANDS_CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::BRANDS_UPDATE, 'sort' => 3],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::BRANDS_DELETE, 'sort' => 4],
        ];
    }

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function productButtonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::PRODUCTS_QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增商品', 'permission' => self::PRODUCTS_CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::PRODUCTS_UPDATE, 'sort' => 3],
            ['name' => 'Status', 'title' => '上下架', 'permission' => self::PRODUCTS_STATUS, 'sort' => 4],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::PRODUCTS_DELETE, 'sort' => 5],
        ];
    }

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function orderButtonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::ORDERS_QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '创建订单', 'permission' => self::ORDERS_CREATE, 'sort' => 2],
            ['name' => 'Prepay', 'title' => '发起支付', 'permission' => self::ORDERS_PREPAY, 'sort' => 3],
            ['name' => 'Cancel', 'title' => '取消订单', 'permission' => self::ORDERS_CANCEL, 'sort' => 4],
            ['name' => 'Refund', 'title' => '退款', 'permission' => self::ORDERS_REFUND, 'sort' => 5],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::ORDERS_DELETE, 'sort' => 6],
        ];
    }
}
