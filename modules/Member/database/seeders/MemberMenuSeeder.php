<?php

namespace Modules\Member\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Member\App\Support\MemberPermissions;
use Modules\System\App\Models\Menu;
use Modules\System\App\Models\Role;
use Modules\System\Database\Seeders\Concerns\SeedsMenus;

class MemberMenuSeeder extends Seeder
{
    use SeedsMenus;

    public function run(): void
    {
        $root = Menu::query()->firstOrCreate(
            ['path' => '/member', 'type' => 1],
            [
                'parent_id' => 0,
                'name' => 'MemberCenter',
                'title' => '会员中心',
                'component' => null,
                'permission' => null,
                'icon' => 'ep:user',
                'sort' => 50,
                'other_settings' => $this->menuOther(1),
                'status' => 1,
            ],
        );

        $list = Menu::query()->firstOrCreate(
            ['path' => '/member/members', 'type' => 2],
            [
                'parent_id' => $root->id,
                'name' => 'MemberList',
                'title' => '会员管理',
                'component' => '/member/index',
                'permission' => MemberPermissions::QUERY,
                'icon' => 'ep:avatar',
                'sort' => 51,
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ],
        );

        $menuIds = [(int) $root->id, (int) $list->id];

        foreach (MemberPermissions::buttonDefinitions() as $btn) {
            $button = Menu::query()->firstOrCreate(
                [
                    'parent_id' => $list->id,
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
