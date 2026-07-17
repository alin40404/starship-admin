<?php

namespace Modules\GameShop\App\Support;

final class GameShopPermissions
{
    public const GAMES_QUERY = 'game_shop.games.query';

    public const GAMES_CREATE = 'game_shop.games.create';

    public const GAMES_UPDATE = 'game_shop.games.update';

    public const GAMES_STATUS = 'game_shop.games.status';

    public const GAMES_DELETE = 'game_shop.games.delete';

    public const PRODUCTS_QUERY = 'game_shop.products.query';

    public const PRODUCTS_CREATE = 'game_shop.products.create';

    public const PRODUCTS_UPDATE = 'game_shop.products.update';

    public const PRODUCTS_STATUS = 'game_shop.products.status';

    public const PRODUCTS_DELETE = 'game_shop.products.delete';

    public const ORDERS_QUERY = 'game_shop.orders.query';

    public const ORDERS_UPDATE = 'game_shop.orders.update';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function gameButtonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::GAMES_QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增', 'permission' => self::GAMES_CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::GAMES_UPDATE, 'sort' => 3],
            ['name' => 'Status', 'title' => '切换状态', 'permission' => self::GAMES_STATUS, 'sort' => 4],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::GAMES_DELETE, 'sort' => 5],
        ];
    }

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function productButtonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::PRODUCTS_QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增', 'permission' => self::PRODUCTS_CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::PRODUCTS_UPDATE, 'sort' => 3],
            ['name' => 'Status', 'title' => '切换状态', 'permission' => self::PRODUCTS_STATUS, 'sort' => 4],
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
            ['name' => 'Update', 'title' => '处理', 'permission' => self::ORDERS_UPDATE, 'sort' => 2],
        ];
    }
}
