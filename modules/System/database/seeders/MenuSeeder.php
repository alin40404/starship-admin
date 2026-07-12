<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Menu;
use Modules\System\App\Support\AdminPermissions;
use Modules\System\App\Support\AreaPermissions;
use Modules\System\App\Support\AttachmentPermissions;
use Modules\System\App\Support\CategoryPermissions;
use Modules\System\App\Support\ConfigPermissions;
use Modules\System\App\Support\DepartmentPermissions;
use Modules\System\App\Support\DictPermissions;
use Modules\System\App\Support\MenuPermissions;
use Modules\System\App\Support\ModulePermissions;
use Modules\System\App\Support\PluginPermissions;
use Modules\System\App\Support\PostPermissions;
use Modules\System\App\Support\RolePermissions;
use Modules\System\App\Support\UserGroupPermissions;
use Modules\System\Database\Seeders\Concerns\SeedsMenus;
use Modules\Generator\App\Support\GeneratorPermissions;
use Modules\Monitor\App\Support\ExceptionLogPermissions;
use Modules\Monitor\App\Support\LoginLogPermissions;
use Modules\Monitor\App\Support\OperationLogPermissions;

class MenuSeeder extends Seeder
{
    use SeedsMenus;

    public function run(): void
    {

        if (Menu::query()->exists()) {
            return;
        }

        $workbench = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => 'Workbench',
            'title' => '工作台',
            'path' => '/dashboard',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:home-filled',
            'sort' => 10,
            'other_settings' => $this->menuOther(1),
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => 'Console', 'title' => '控制台', 'path' => '/dashboard/console', 'permission' => 'dashboard.view', 'sort' => 11, 'icon' => 'ep:odometer', 'keep_alive' => 0],
                ['name' => 'Overview', 'title' => '数据概览', 'path' => '/dashboard/overview', 'permission' => 'dashboard.overview', 'sort' => 12, 'icon' => 'ep:histogram', 'keep_alive' => 1],
                ['name' => 'TodoCenter', 'title' => '待办中心', 'path' => '/dashboard/todos', 'permission' => 'dashboard.todos', 'sort' => 13, 'icon' => 'ep:list', 'keep_alive' => 1],
                ['name' => 'QuickEntry', 'title' => '快捷入口', 'path' => '/dashboard/quick-entry', 'permission' => 'dashboard.quick_entry', 'sort' => 14, 'icon' => 'ep:promotion', 'keep_alive' => 0],
                ['name' => 'Favorites', 'title' => '我的收藏', 'path' => '/dashboard/favorites', 'permission' => 'dashboard.favorites', 'sort' => 15, 'icon' => 'ep:star-filled', 'keep_alive' => 1],
                ['name' => 'InboxCenter', 'title' => '消息中心', 'path' => '/dashboard/inbox', 'permission' => 'dashboard.inbox', 'sort' => 16, 'icon' => 'ep:message', 'keep_alive' => 1],
            ] as $m
        ) {
            Menu::query()->create([
                'parent_id' => $workbench->id,
                'type' => 2,
                'name' => $m['name'],
                'title' => $m['title'],
                'path' => $m['path'],
                'component' => $this->defaultComponentForMenuPath($m['path']),
                'permission' => $m['permission'],
                'icon' => $m['icon'],
                'sort' => $m['sort'],
                'other_settings' => $this->menuOther(1, $m['keep_alive']),
                'status' => 1,
            ]);
        }

        $access = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => 'Access',
            'title' => '权限中心',
            'path' => '/permission',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:lock',
            'sort' => 100,
            'other_settings' => $this->menuOther(1),
            'status' => 1,
        ]);

        $menuMgmt = Menu::query()->create([
            'parent_id' => $access->id,
            'type' => 2,
            'name' => 'MenuMgmt',
            'title' => '菜单管理',
            'path' => '/permission/menus',
            'component' => $this->defaultComponentForMenuPath('/permission/menus'),
            'permission' => 'system.menus',
            'icon' => 'ep:menu',
            'sort' => 101,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (MenuPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $menuMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $roleMgmt = Menu::query()->create([
            'parent_id' => $access->id,
            'type' => 2,
            'name' => 'RoleMgmt',
            'title' => '角色管理',
            'path' => '/permission/roles',
            'component' => $this->defaultComponentForMenuPath('/permission/roles'),
            'permission' => 'system.roles',
            'icon' => 'ep:user-filled',
            'sort' => 102,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (RolePermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $roleMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $adminMenu = Menu::query()->create([
            'parent_id' => $access->id,
            'type' => 2,
            'name' => 'AdminMgmt',
            'title' => '管理员',
            'path' => '/permission/admins',
            'component' => $this->defaultComponentForMenuPath('/permission/admins'),
            'permission' => 'system.admins',
            'icon' => 'ep:user',
            'sort' => 103,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (AdminPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $adminMenu->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $org = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => 'Org',
            'title' => '组织架构',
            'path' => '/org',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:share',
            'sort' => 200,
            'other_settings' => $this->menuOther(1),
            'status' => 1,
        ]);

        $deptMgmt = Menu::query()->create([
            'parent_id' => $org->id,
            'type' => 2,
            'name' => 'DeptMgmt',
            'title' => '部门管理',
            'path' => '/org/departments',
            'component' => $this->defaultComponentForMenuPath('/org/departments'),
            'permission' => 'system.departments',
            'icon' => 'ep:office-building',
            'sort' => 201,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (DepartmentPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $deptMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $postMgmt = Menu::query()->create([
            'parent_id' => $org->id,
            'type' => 2,
            'name' => 'PostMgmt',
            'title' => '岗位管理',
            'path' => '/org/posts',
            'component' => $this->defaultComponentForMenuPath('/org/posts'),
            'permission' => 'system.posts',
            'icon' => 'ep:postcard',
            'sort' => 202,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (PostPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $postMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $userGroupMgmt = Menu::query()->create([
            'parent_id' => $org->id,
            'type' => 2,
            'name' => 'UserGroupMgmt',
            'title' => '用户组管理',
            'path' => '/org/user-groups',
            'component' => $this->defaultComponentForMenuPath('/org/user-groups'),
            'permission' => 'system.user_groups',
            'icon' => 'ep:avatar',
            'sort' => 203,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (UserGroupPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $userGroupMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $platform = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => 'Platform',
            'title' => '平台设置',
            'path' => '/platform',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:operation',
            'sort' => 300,
            'other_settings' => $this->menuOther(1),
            'status' => 1,
        ]);

        $configMgmt = Menu::query()->create([
            'parent_id' => $platform->id,
            'type' => 2,
            'name' => 'ConfigMgmt',
            'title' => '系统配置',
            'path' => '/platform/configs',
            'component' => $this->defaultComponentForMenuPath('/platform/configs'),
            'permission' => 'system.configs',
            'icon' => 'ep:setting',
            'sort' => 301,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (ConfigPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $configMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $dictMgmt = Menu::query()->create([
            'parent_id' => $platform->id,
            'type' => 2,
            'name' => 'DictMgmt',
            'title' => '字典管理',
            'path' => '/platform/dicts',
            'component' => $this->defaultComponentForMenuPath('/platform/dicts'),
            'permission' => 'system.dicts',
            'icon' => 'ep:collection',
            'sort' => 302,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (DictPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $dictMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $attachmentMgmt = Menu::query()->create([
            'parent_id' => $platform->id,
            'type' => 2,
            'name' => 'AttachmentMgmt',
            'title' => '附件管理',
            'path' => '/resource/attachments',
            'component' => $this->defaultComponentForMenuPath('/resource/attachments'),
            'permission' => 'system.attachments',
            'icon' => 'ep:paperclip',
            'sort' => 303,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (AttachmentPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $attachmentMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $categoryMgmt = Menu::query()->create([
            'parent_id' => $platform->id,
            'type' => 2,
            'name' => 'CategoryMgmt',
            'title' => '分类管理',
            'path' => '/platform/categories',
            'component' => $this->defaultComponentForMenuPath('/platform/categories'),
            'permission' => 'system.categories',
            'icon' => 'ep:collection-tag',
            'sort' => 304,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (CategoryPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $categoryMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $regionMgmt = Menu::query()->create([
            'parent_id' => $platform->id,
            'type' => 2,
            'name' => 'RegionMgmt',
            'title' => '区域管理',
            'path' => '/platform/regions',
            'component' => $this->defaultComponentForMenuPath('/platform/regions'),
            'permission' => 'system.regions',
            'icon' => 'ep:location',
            'sort' => 305,
            'other_settings' => $this->menuOther(1, 1),
            'status' => 1,
        ]);

        foreach (AreaPermissions::buttonDefinitions() as $btn) {
            Menu::query()->create([
                'parent_id' => $regionMgmt->id,
                'type' => 3,
                'name' => $btn['name'],
                'title' => $btn['title'],
                'path' => null,
                'component' => null,
                'permission' => $btn['permission'],
                'icon' => null,
                'sort' => $btn['sort'],
                'other_settings' => $this->menuOther(0),
                'status' => 1,
            ]);
        }

        $monitor = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => 'Monitor',
            'title' => '监控中心',
            'path' => '/monitor',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:monitor',
            'sort' => 500,
            'other_settings' => $this->menuOther(1),
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => 'OpLog', 'title' => '操作日志', 'path' => '/monitor/operation-logs', 'permission' => OperationLogPermissions::MANAGE, 'sort' => 501, 'icon' => 'ep:document', 'buttons' => OperationLogPermissions::buttonDefinitions()],
                ['name' => 'LoginLog', 'title' => '登录日志', 'path' => '/monitor/login-logs', 'permission' => LoginLogPermissions::MANAGE, 'sort' => 502, 'icon' => 'ep:notebook', 'buttons' => LoginLogPermissions::buttonDefinitions()],
                ['name' => 'ExceptionLog', 'title' => '异常日志', 'path' => '/monitor/exception-logs', 'permission' => ExceptionLogPermissions::MANAGE, 'sort' => 503, 'icon' => 'ep:warning-filled', 'buttons' => ExceptionLogPermissions::buttonDefinitions()],
            ] as $m
        ) {
            $monitorMenu = Menu::query()->create([
                'parent_id' => $monitor->id,
                'type' => 2,
                'name' => $m['name'],
                'title' => $m['title'],
                'path' => $m['path'],
                'component' => $this->defaultComponentForMenuPath($m['path']),
                'permission' => $m['permission'],
                'icon' => $m['icon'],
                'sort' => $m['sort'],
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ]);

            foreach ($m['buttons'] as $btn) {
                Menu::query()->create([
                    'parent_id' => $monitorMenu->id,
                    'type' => 3,
                    'name' => $btn['name'],
                    'title' => $btn['title'],
                    'path' => null,
                    'component' => null,
                    'permission' => $btn['permission'],
                    'icon' => null,
                    'sort' => $btn['sort'],
                    'other_settings' => $this->menuOther(0),
                    'status' => 1,
                ]);
            }
        }

        $dev = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => 'DevTools',
            'title' => '开发工具',
            'path' => '/dev',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:cpu',
            'sort' => 600,
            'other_settings' => $this->menuOther(1),
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => 'Codegen', 'title' => '代码生成', 'path' => '/dev/codegen', 'permission' => GeneratorPermissions::MANAGE, 'sort' => 601, 'icon' => 'ep:magic-stick'],
                ['name' => 'ModuleMgmt', 'title' => '模块管理', 'path' => '/dev/modules', 'permission' => 'system.modules', 'sort' => 602, 'icon' => 'ep:grid'],
                ['name' => 'PluginMgmt', 'title' => '插件管理', 'path' => '/dev/plugins', 'permission' => 'system.plugins', 'sort' => 603, 'icon' => 'ep:connection'],
            ] as $m
        ) {
            $devMenu = Menu::query()->create([
                'parent_id' => $dev->id,
                'type' => 2,
                'name' => $m['name'],
                'title' => $m['title'],
                'path' => $m['path'],
                'component' => $this->defaultComponentForMenuPath($m['path']),
                'permission' => $m['permission'],
                'icon' => $m['icon'],
                'sort' => $m['sort'],
                'other_settings' => $this->menuOther(1, 1),
                'status' => 1,
            ]);

            if ($m['path'] === '/dev/codegen') {
                foreach (GeneratorPermissions::buttonDefinitions() as $btn) {
                    Menu::query()->create([
                        'parent_id' => $devMenu->id,
                        'type' => 3,
                        'name' => $btn['name'],
                        'title' => $btn['title'],
                        'path' => null,
                        'component' => null,
                        'permission' => $btn['permission'],
                        'icon' => null,
                        'sort' => $btn['sort'],
                        'other_settings' => $this->menuOther(0),
                        'status' => 1,
                    ]);
                }
            }

            if ($m['path'] === '/dev/modules') {
                foreach (ModulePermissions::buttonDefinitions() as $btn) {
                    Menu::query()->create([
                        'parent_id' => $devMenu->id,
                        'type' => 3,
                        'name' => $btn['name'],
                        'title' => $btn['title'],
                        'path' => null,
                        'component' => null,
                        'permission' => $btn['permission'],
                        'icon' => null,
                        'sort' => $btn['sort'],
                        'other_settings' => $this->menuOther(0),
                        'status' => 1,
                    ]);
                }
            }

            if ($m['path'] === '/dev/plugins') {
                foreach (PluginPermissions::buttonDefinitions() as $btn) {
                    Menu::query()->create([
                        'parent_id' => $devMenu->id,
                        'type' => 3,
                        'name' => $btn['name'],
                        'title' => $btn['title'],
                        'path' => null,
                        'component' => null,
                        'permission' => $btn['permission'],
                        'icon' => null,
                        'sort' => $btn['sort'],
                        'other_settings' => $this->menuOther(0),
                        'status' => 1,
                    ]);
                }
            }
        }
        }
}
