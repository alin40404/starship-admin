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

        $workbench = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => '工作台',
            'path' => '/dashboard',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:home-filled',
            'sort' => 10,
            'visible' => 1,
            'keep_alive' => 0,
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => '控制台', 'path' => '/dashboard/console', 'permission' => 'dashboard.view', 'sort' => 11, 'icon' => 'ep:odometer', 'keep_alive' => 0],
                ['name' => '数据概览', 'path' => '/dashboard/overview', 'permission' => 'dashboard.overview', 'sort' => 12, 'icon' => 'ep:histogram', 'keep_alive' => 1],
                ['name' => '待办中心', 'path' => '/dashboard/todos', 'permission' => 'dashboard.todos', 'sort' => 13, 'icon' => 'ep:list', 'keep_alive' => 1],
                ['name' => '快捷入口', 'path' => '/dashboard/quick-entry', 'permission' => 'dashboard.quick_entry', 'sort' => 14, 'icon' => 'ep:promotion', 'keep_alive' => 0],
                ['name' => '我的收藏', 'path' => '/dashboard/favorites', 'permission' => 'dashboard.favorites', 'sort' => 15, 'icon' => 'ep:star-filled', 'keep_alive' => 1],
            ] as $m
        ) {
            Menu::query()->create([
                'parent_id' => $workbench->id,
                'type' => 2,
                'name' => $m['name'],
                'path' => $m['path'],
                'component' => null,
                'permission' => $m['permission'],
                'icon' => $m['icon'],
                'sort' => $m['sort'],
                'visible' => 1,
                'keep_alive' => $m['keep_alive'],
                'status' => 1,
            ]);
        }

        $access = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => '权限中心',
            'path' => '/permission',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:lock',
            'sort' => 100,
            'visible' => 1,
            'keep_alive' => 0,
            'status' => 1,
        ]);

        Menu::query()->create([
            'parent_id' => $access->id,
            'type' => 2,
            'name' => '菜单管理',
            'path' => '/permission/menus',
            'component' => null,
            'permission' => 'system.menus',
            'icon' => 'ep:menu',
            'sort' => 101,
            'visible' => 1,
            'keep_alive' => 1,
            'status' => 1,
        ]);

        Menu::query()->create([
            'parent_id' => $access->id,
            'type' => 2,
            'name' => '角色管理',
            'path' => '/permission/roles',
            'component' => null,
            'permission' => 'system.roles',
            'icon' => 'ep:user-filled',
            'sort' => 102,
            'visible' => 1,
            'keep_alive' => 1,
            'status' => 1,
        ]);

        $adminMenu = Menu::query()->create([
            'parent_id' => $access->id,
            'type' => 2,
            'name' => '管理员',
            'path' => '/permission/admins',
            'component' => null,
            'permission' => 'system.admins',
            'icon' => 'ep:user',
            'sort' => 103,
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

        Menu::query()->create([
            'parent_id' => $access->id,
            'type' => 2,
            'name' => '权限点管理',
            'path' => '/permission/permission-points',
            'component' => null,
            'permission' => 'system.permission_points',
            'icon' => 'ep:key',
            'sort' => 104,
            'visible' => 1,
            'keep_alive' => 1,
            'status' => 1,
        ]);

        Menu::query()->create([
            'parent_id' => $access->id,
            'type' => 2,
            'name' => '数据权限',
            'path' => '/permission/data-permission',
            'component' => null,
            'permission' => 'system.data_permission',
            'icon' => 'ep:trend-charts',
            'sort' => 105,
            'visible' => 1,
            'keep_alive' => 1,
            'status' => 1,
        ]);

        $org = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => '组织架构',
            'path' => '/org',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:share',
            'sort' => 200,
            'visible' => 1,
            'keep_alive' => 0,
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => '部门管理', 'path' => '/org/departments', 'permission' => 'system.departments', 'sort' => 201, 'icon' => 'ep:office-building'],
                ['name' => '岗位管理', 'path' => '/org/posts', 'permission' => 'system.posts', 'sort' => 202, 'icon' => 'ep:postcard'],
                ['name' => '用户组管理', 'path' => '/org/user-groups', 'permission' => 'system.user_groups', 'sort' => 203, 'icon' => 'ep:avatar'],
            ] as $m
        ) {
            Menu::query()->create([
                'parent_id' => $org->id,
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

        $platform = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => '平台设置',
            'path' => '/platform',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:operation',
            'sort' => 300,
            'visible' => 1,
            'keep_alive' => 0,
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => '字典管理', 'path' => '/platform/dicts', 'permission' => 'system.dicts', 'sort' => 301, 'icon' => 'ep:collection'],
                ['name' => '系统配置', 'path' => '/platform/configs', 'permission' => 'system.configs', 'sort' => 302, 'icon' => 'ep:setting'],
                ['name' => '参数配置', 'path' => '/platform/params', 'permission' => 'system.params', 'sort' => 303, 'icon' => 'ep:setting'],
                ['name' => '区域管理', 'path' => '/platform/regions', 'permission' => 'system.regions', 'sort' => 304, 'icon' => 'ep:location'],
                ['name' => '通知配置', 'path' => '/platform/notifications', 'permission' => 'system.notifications', 'sort' => 305, 'icon' => 'ep:bell'],
            ] as $m
        ) {
            Menu::query()->create([
                'parent_id' => $platform->id,
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

        $resource = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => '资源管理',
            'path' => '/resource',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:folder-opened',
            'sort' => 400,
            'visible' => 1,
            'keep_alive' => 0,
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => '附件管理', 'path' => '/resource/attachments', 'permission' => 'system.attachments', 'sort' => 401, 'icon' => 'ep:paperclip'],
                ['name' => '文件分组', 'path' => '/resource/file-groups', 'permission' => 'system.file_groups', 'sort' => 402, 'icon' => 'ep:files'],
                ['name' => '存储配置', 'path' => '/resource/storage', 'permission' => 'system.storage', 'sort' => 403, 'icon' => 'ep:coin'],
            ] as $m
        ) {
            Menu::query()->create([
                'parent_id' => $resource->id,
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

        $monitor = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => '监控中心',
            'path' => '/monitor',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:monitor',
            'sort' => 500,
            'visible' => 1,
            'keep_alive' => 0,
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => '操作日志', 'path' => '/monitor/operation-logs', 'permission' => 'system.operation_logs', 'sort' => 501, 'icon' => 'ep:document'],
                ['name' => '登录日志', 'path' => '/monitor/login-logs', 'permission' => 'system.login_logs', 'sort' => 502, 'icon' => 'ep:notebook'],
                ['name' => '异常日志', 'path' => '/monitor/exception-logs', 'permission' => 'system.exception_logs', 'sort' => 503, 'icon' => 'ep:warning-filled'],
                ['name' => '在线用户', 'path' => '/monitor/online-users', 'permission' => 'system.online_users', 'sort' => 504, 'icon' => 'ep:view'],
                ['name' => '定时任务', 'path' => '/monitor/scheduled-tasks', 'permission' => 'system.scheduled_tasks', 'sort' => 505, 'icon' => 'ep:timer'],
            ] as $m
        ) {
            Menu::query()->create([
                'parent_id' => $monitor->id,
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

        $dev = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => '开发工具',
            'path' => '/dev',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:cpu',
            'sort' => 600,
            'visible' => 1,
            'keep_alive' => 0,
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => '代码生成', 'path' => '/dev/codegen', 'permission' => 'system.codegen', 'sort' => 601, 'icon' => 'ep:magic-stick'],
                ['name' => '模块管理', 'path' => '/dev/modules', 'permission' => 'system.modules', 'sort' => 602, 'icon' => 'ep:grid'],
                ['name' => '接口文档', 'path' => '/dev/api-docs', 'permission' => 'system.api_docs', 'sort' => 603, 'icon' => 'ep:link'],
            ] as $m
        ) {
            Menu::query()->create([
                'parent_id' => $dev->id,
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
