<?php

namespace Modules\Shop\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shop\App\Support\ShopPermissions;
use Modules\System\App\Models\Menu;
use Modules\System\App\Models\Role;
use Modules\System\Database\Seeders\Concerns\SeedsMenus;

class ShopMenuSeeder extends Seeder
{
    use SeedsMenus;

    public function run(): void
    {
        $root = Menu::query()->firstOrCreate(
            ['path' => '/shop', 'type' => 1],
            [
                'parent_id' => 0,
                'name' => 'ShopCenter',
                'title' => '商城',
                'component' => null,
                'permission' => null,
                'icon' => 'ep:shopping-cart',
                'sort' => 60,
                'other_settings' => $this->menuOther(1),
                'status' => 1,
            ],
        );

        $products = Menu::query()->firstOrCreate(
            ['path' => '/shop/products', 'type' => 2],
            [
                'parent_id' => $root->id,
                'name' => 'ShopProducts',
                'title' => '商品管理',
                'component' => '/shop/products/index',
                'permission' => ShopPermissions::PRODUCTS_QUERY,
                'icon' => 'ep:goods',
                'sort' => 61,
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ],
        );

        $orders = Menu::query()->firstOrCreate(
            ['path' => '/shop/orders', 'type' => 2],
            [
                'parent_id' => $root->id,
                'name' => 'ShopOrders',
                'title' => '订单管理',
                'component' => '/shop/orders/index',
                'permission' => ShopPermissions::ORDERS_QUERY,
                'icon' => 'ep:document',
                'sort' => 62,
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ],
        );

        $menuIds = [(int) $root->id, (int) $products->id, (int) $orders->id];

        foreach (ShopPermissions::productButtonDefinitions() as $btn) {
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

        foreach (ShopPermissions::orderButtonDefinitions() as $btn) {
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
