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
    /** @var list<string> */
    private const NORMAL_ADMIN_PLATFORM_MENUS = [
        'AttachmentMgmt',
        'CategoryMgmt',
    ];

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

        $this->syncSuperAdminMenus();
        $this->syncNormalAdminMenus();
    }

    private function syncSuperAdminMenus(): void
    {
        $role = Role::query()->where('code', 'super_admin')->first();
        if (! $role) {
            return;
        }

        $menuIds = Menu::query()->where('status', 1)->pluck('id')->all();
        $role->menus()->sync($menuIds);
    }

    private function syncNormalAdminMenus(): void
    {
        $role = Role::query()->where('code', 'normal_admin')->first();
        if (! $role) {
            return;
        }

        $menuIds = [];

        $workbench = Menu::query()
            ->where('name', 'Workbench')
            ->where('status', 1)
            ->first();
        if ($workbench) {
            $menuIds = array_merge($menuIds, $this->collectMenuSubtreeIds((int) $workbench->id));
        }

        $platform = Menu::query()
            ->where('name', 'Platform')
            ->where('status', 1)
            ->first();
        if ($platform) {
            $menuIds[] = (int) $platform->id;

            $children = Menu::query()
                ->where('parent_id', $platform->id)
                ->whereIn('name', self::NORMAL_ADMIN_PLATFORM_MENUS)
                ->where('status', 1)
                ->pluck('id')
                ->all();

            foreach ($children as $childId) {
                $menuIds = array_merge($menuIds, $this->collectMenuSubtreeIds((int) $childId));
            }
        }

        $role->menus()->sync(array_values(array_unique($menuIds)));
    }

    /**
     * @return list<int>
     */
    private function collectMenuSubtreeIds(int $rootMenuId): array
    {
        $ids = [$rootMenuId];

        $children = Menu::query()
            ->where('parent_id', $rootMenuId)
            ->where('status', 1)
            ->pluck('id')
            ->all();

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->collectMenuSubtreeIds((int) $childId));
        }

        return $ids;
    }
}
