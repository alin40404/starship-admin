<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Department;
use Modules\System\App\Models\DictItem;
use Modules\System\App\Models\DictType;
use Modules\System\App\Models\Menu;
use Modules\System\App\Models\Role;
use Modules\System\App\Models\Setting;

/**
 * 安装后最小可用数据：超管、超管角色、系统菜单树、角色-菜单、管理员-角色；可选字典与站点配置。
 */
class StarshipFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::query()->updateOrCreate(
            ['code' => 'super_admin'],
            [
                'name' => '超级管理员',
                'data_scope' => 1,
                'status' => 1,
                'sort' => 0,
                'remark' => '系统内置，绑定全部菜单',
            ]
        );

        $this->seedMenus();
        $menuIds = Menu::query()->where('status', 1)->pluck('id')->all();
        $role->menus()->sync($menuIds);

        $password = (string) env('SYSTEM_ADMIN_PASSWORD', 'admin123');

        $dept = Department::query()->firstOrCreate(
            ['parent_id' => 0, 'name' => '总公司'],
            ['sort' => 0, 'status' => 1]
        );

        $admin = Admin::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'nickname' => '超级管理员',
                'password' => $password,
                'dept_id' => $dept->id,
                'status' => 1,
                'is_super' => 1,
            ]
        );

        $admin->roles()->sync([$role->id]);

        $this->seedDictBasics();
        $this->seedConfigBasics();
    }

    private function seedMenus(): void
    {
        if (Menu::query()->exists()) {
            return;
        }

        Menu::query()->create([
            'parent_id' => 0,
            'type' => 2,
            'name' => '工作台',
            'path' => '/dashboard',
            'component' => null,
            'permission' => 'dashboard.view',
            'icon' => 'HomeFilled',
            'sort' => 10,
            'visible' => 1,
            'keep_alive' => 0,
            'status' => 1,
        ]);

        $system = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => '系统管理',
            'path' => '/system',
            'component' => null,
            'permission' => null,
            'icon' => 'Setting',
            'sort' => 100,
            'visible' => 1,
            'keep_alive' => 0,
            'status' => 1,
        ]);

        $adminMenu = Menu::query()->create([
            'parent_id' => $system->id,
            'type' => 2,
            'name' => '管理员',
            'path' => '/system/admins',
            'component' => null,
            'permission' => 'system.admins',
            'icon' => 'User',
            'sort' => 110,
            'visible' => 1,
            'keep_alive' => 1,
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => '查询', 'permission' => 'system.admins.query', 'sort' => 1],
                ['name' => '新增', 'permission' => 'system.admins.create', 'sort' => 2],
                ['name' => '编辑', 'permission' => 'system.admins.update', 'sort' => 3],
                ['name' => '删除', 'permission' => 'system.admins.delete', 'sort' => 4],
            ] as $btn
        ) {
            Menu::query()->create([
                'parent_id' => $adminMenu->id,
                'type' => 3,
                'name' => $btn['name'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'visible' => 0,
                'keep_alive' => 0,
                'status' => 1,
            ]);
        }

        $menus = [
            ['name' => '角色管理', 'path' => '/system/roles', 'permission' => 'system.roles', 'sort' => 120, 'icon' => 'UserFilled'],
            ['name' => '菜单管理', 'path' => '/system/menus', 'permission' => 'system.menus', 'sort' => 130, 'icon' => 'Menu'],
            ['name' => '部门管理', 'path' => '/system/departments', 'permission' => 'system.departments', 'sort' => 140, 'icon' => 'OfficeBuilding'],
            ['name' => '岗位管理', 'path' => '/system/posts', 'permission' => 'system.posts', 'sort' => 150, 'icon' => 'Postcard'],
            ['name' => '字典管理', 'path' => '/system/dicts', 'permission' => 'system.dicts', 'sort' => 160, 'icon' => 'Collection'],
            ['name' => '系统配置', 'path' => '/system/configs', 'permission' => 'system.configs', 'sort' => 170, 'icon' => 'Tools'],
            ['name' => '附件管理', 'path' => '/system/attachments', 'permission' => 'system.attachments', 'sort' => 180, 'icon' => 'Paperclip'],
        ];

        foreach ($menus as $m) {
            Menu::query()->create([
                'parent_id' => $system->id,
                'type' => 2,
                'name' => $m['name'],
                'path' => $m['path'],
                'component' => null,
                'permission' => $m['permission'],
                'icon' => $m['icon'],
                'sort' => $m['sort'],
                'visible' => 1,
                'keep_alive' => 1,
                'status' => 1,
            ]);
        }

    }

    private function seedDictBasics(): void
    {
        $type = DictType::query()->updateOrCreate(
            ['code' => 'sys_status'],
            [
                'name' => '通用状态',
                'status' => 1,
                'remark' => '启用 / 禁用',
            ]
        );

        DictItem::query()->updateOrCreate(
            ['dict_type_id' => $type->id, 'value' => '1'],
            ['label' => '启用', 'sort' => 1, 'status' => 1]
        );

        DictItem::query()->updateOrCreate(
            ['dict_type_id' => $type->id, 'value' => '0'],
            ['label' => '禁用', 'sort' => 2, 'status' => 1]
        );
    }

    private function seedConfigBasics(): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'site.name'],
            [
                'name' => '站点名称',
                'value' => 'Starship Admin',
                'group_name' => 'site',
                'type' => 'string',
                'sort' => 0,
                'remark' => '后台左上角展示名称',
            ]
        );
    }
}
