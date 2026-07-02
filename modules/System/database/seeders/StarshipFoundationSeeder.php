<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Menu;
use Modules\System\App\Models\Role;

/**
 * System 模块基础数据编排入口：按领域依次初始化各子 Seeder。
 */
class StarshipFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            MenuSeeder::class,
            OrganizationSeeder::class,
            AdminSeeder::class,
            DictSeeder::class,
            CategorySeeder::class,
            ConfigSeeder::class,
            AreaSeeder::class,
        ]);

        $role = Role::query()->where('code', 'super_admin')->first();
        if ($role) {
            $menuIds = Menu::query()->where('status', 1)->pluck('id')->all();
            $role->menus()->sync($menuIds);
        }
    }
}
