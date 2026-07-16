<?php

namespace Modules\Shop\App\Support;

final class ShopPermissions
{
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
