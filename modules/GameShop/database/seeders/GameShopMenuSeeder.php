<?php

namespace Modules\GameShop\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\GameShop\App\Support\GameShopPermissions;
use Modules\System\App\Models\Menu;
use Modules\System\App\Models\Role;
use Modules\System\Database\Seeders\Concerns\SeedsMenus;

class GameShopMenuSeeder extends Seeder
{
    use SeedsMenus;

    public function run(): void
    {
        $root = Menu::query()->firstOrCreate(
            ['path' => '/game-shop', 'type' => 1],
            [
                'parent_id' => 0,
                'name' => 'GameShopCenter',
                'title' => '游戏商城',
                'component' => null,
                'permission' => null,
                'icon' => 'ep:trophy',
                'sort' => 65,
                'other_settings' => $this->menuOther(1),
                'status' => 1,
            ],
        );

        $games = Menu::query()->firstOrCreate(
            ['path' => '/game-shop/games', 'type' => 2],
            [
                'parent_id' => $root->id,
                'name' => 'GameShopGames',
                'title' => '游戏管理',
                'component' => '/game-shop/games/index',
                'permission' => GameShopPermissions::GAMES_QUERY,
                'icon' => 'ep:monitor',
                'sort' => 1,
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ],
        );

        $products = Menu::query()->firstOrCreate(
            ['path' => '/game-shop/products', 'type' => 2],
            [
                'parent_id' => $root->id,
                'name' => 'GameShopProducts',
                'title' => '游戏商品',
                'component' => '/game-shop/products/index',
                'permission' => GameShopPermissions::PRODUCTS_QUERY,
                'icon' => 'ep:goods',
                'sort' => 2,
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ],
        );

        $orders = Menu::query()->firstOrCreate(
            ['path' => '/game-shop/orders', 'type' => 2],
            [
                'parent_id' => $root->id,
                'name' => 'GameShopOrders',
                'title' => '游戏订单',
                'component' => '/game-shop/orders/index',
                'permission' => GameShopPermissions::ORDERS_QUERY,
                'icon' => 'ep:list',
                'sort' => 3,
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ],
        );

        $menuIds = [(int) $root->id, (int) $games->id, (int) $products->id, (int) $orders->id];

        foreach (GameShopPermissions::gameButtonDefinitions() as $btn) {
            $button = Menu::query()->firstOrCreate(
                [
                    'parent_id' => $games->id,
                    'type' => 3,
                    'permission' => $btn['permission'],
                ],
                [
                    'name' => $btn['name'],
                    'title' => $btn['title'],
                    'path' => null,
                    'component' => null,
                    'icon' => null,
                    'sort' => $btn['sort'],
                    'other_settings' => $this->menuOther(0),
                    'status' => 1,
                ],
            );
            $menuIds[] = (int) $button->id;
        }

        foreach (GameShopPermissions::productButtonDefinitions() as $btn) {
            $button = Menu::query()->firstOrCreate(
                [
                    'parent_id' => $products->id,
                    'type' => 3,
                    'permission' => $btn['permission'],
                ],
                [
                    'name' => $btn['name'],
                    'title' => $btn['title'],
                    'path' => null,
                    'component' => null,
                    'icon' => null,
                    'sort' => $btn['sort'],
                    'other_settings' => $this->menuOther(0),
                    'status' => 1,
                ],
            );
            $menuIds[] = (int) $button->id;
        }

        foreach (GameShopPermissions::orderButtonDefinitions() as $btn) {
            $button = Menu::query()->firstOrCreate(
                [
                    'parent_id' => $orders->id,
                    'type' => 3,
                    'permission' => $btn['permission'],
                ],
                [
                    'name' => $btn['name'],
                    'title' => $btn['title'],
                    'path' => null,
                    'component' => null,
                    'icon' => null,
                    'sort' => $btn['sort'],
                    'other_settings' => $this->menuOther(0),
                    'status' => 1,
                ],
            );
            $menuIds[] = (int) $button->id;
        }

        $role = Role::query()->where('code', 'super_admin')->first();
        if ($role !== null) {
            $role->menus()->syncWithoutDetaching($menuIds);
        }
    }
}
