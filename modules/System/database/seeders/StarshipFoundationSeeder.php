<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Department;
use Modules\System\App\Models\DictItem;
use Modules\System\App\Models\DictType;
use Modules\System\App\Models\Menu;
use Modules\System\App\Models\Post;
use Modules\System\App\Support\AdminPermissions;
use Modules\System\App\Support\DepartmentPermissions;
use Modules\System\App\Support\MenuPermissions;
use Modules\System\App\Support\PostPermissions;
use Modules\System\App\Support\RolePermissions;
use Modules\System\App\Support\UserGroupPermissions;
use Modules\System\App\Models\Role;
use Modules\System\App\Models\Setting;

/**
 * System 模块基础数据：超管、角色、菜单树、字典与站点配置。
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
                'remark' => '系统内置，绑定全部菜单',
            ]
        );

        $this->seedMenus();
        $menuIds = Menu::query()->where('status', 1)->pluck('id')->all();
        $role->menus()->sync($menuIds);

        $password = (string) env('SYSTEM_ADMIN_PASSWORD', 'admin123');

        $dept = $this->seedDepartments();
        $this->seedPosts();

        $admin = Admin::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'nickname' => '超级管理员',
                'password' => $password,
                'email' => 'admin@starship.org',
                'mobile' => '13800000000',
                'dept_id' => $dept->id,
                'status' => 1,
                'is_super' => 1,
            ]
        );

        $admin->roles()->sync([$role->id]);

        $this->seedDictBasics();
        $this->seedConfigBasics();
    }

    /**
     * @param  int  $legacyVisible  1=侧栏显示 0=侧栏隐藏（对应旧 visible）
     */
    private function menuOther(int $legacyVisible, int $keepAlive = 0, int $affix = 0): array
    {
        return Menu::normalizeOtherSettings([
            'hide_menu' => $legacyVisible === 0 ? 1 : 0,
            'keep_alive' => $keepAlive,
            'affix_tab' => $affix,
        ]);
    }

    /**
     * 与 web-antd `router/routes` 中 componentKeys 一致：`/` 开头、无 `views/`、无 `.vue`（与菜单「页面组件」AutoComplete 存库格式一致）。
     */
    private function defaultComponentForMenuPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if ($path === '/dashboard' || $path === '/dashboard/console') {
            return '/dashboard/workspace/index';
        }
        if ($path === '/dashboard/overview') {
            return '/dashboard/analytics/index';
        }
        $permission = [
            '/permission/admins' => '/permission/admin/index',
            '/permission/roles' => '/permission/role/index',
            '/permission/menus' => '/permission/menu/index',
            '/org/departments' => '/org/department/index',
            '/org/posts' => '/org/post/index',
            '/org/user-groups' => '/org/user-group/index',
            '/platform/dicts' => '/platform/dict/index',
            '/platform/configs' => '/platform/config/index',
            '/platform/params' => '/platform/param/index',
            '/platform/regions' => '/platform/region/index',
            '/platform/notifications' => '/platform/notification/index',
        ];
        if (isset($permission[$path])) {
            return $permission[$path];
        }

        return '/_core/fallback/coming-soon';
    }

    private function seedMenus(): void
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

        foreach (
            [
                ['name' => 'DictMgmt', 'title' => '字典管理', 'path' => '/platform/dicts', 'permission' => 'system.dicts', 'sort' => 301, 'icon' => 'ep:collection'],
                ['name' => 'ConfigMgmt', 'title' => '系统配置', 'path' => '/platform/configs', 'permission' => 'system.configs', 'sort' => 302, 'icon' => 'ep:setting'],
                ['name' => 'ParamMgmt', 'title' => '参数配置', 'path' => '/platform/params', 'permission' => 'system.params', 'sort' => 303, 'icon' => 'ep:setting'],
                ['name' => 'RegionMgmt', 'title' => '区域管理', 'path' => '/platform/regions', 'permission' => 'system.regions', 'sort' => 304, 'icon' => 'ep:location'],
                ['name' => 'NotifyMgmt', 'title' => '通知配置', 'path' => '/platform/notifications', 'permission' => 'system.notifications', 'sort' => 305, 'icon' => 'ep:bell'],
            ] as $m
        ) {
            Menu::query()->create([
                'parent_id' => $platform->id,
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
        }

        $resource = Menu::query()->create([
            'parent_id' => 0,
            'type' => 1,
            'name' => 'Resource',
            'title' => '资源管理',
            'path' => '/resource',
            'component' => null,
            'permission' => null,
            'icon' => 'ep:folder-opened',
            'sort' => 400,
            'other_settings' => $this->menuOther(1),
            'status' => 1,
        ]);

        foreach (
            [
                ['name' => 'AttachmentMgmt', 'title' => '附件管理', 'path' => '/resource/attachments', 'permission' => 'system.attachments', 'sort' => 401, 'icon' => 'ep:paperclip'],
                ['name' => 'FileGroupMgmt', 'title' => '文件分组', 'path' => '/resource/file-groups', 'permission' => 'system.file_groups', 'sort' => 402, 'icon' => 'ep:files'],
                ['name' => 'StorageMgmt', 'title' => '存储配置', 'path' => '/resource/storage', 'permission' => 'system.storage', 'sort' => 403, 'icon' => 'ep:coin'],
            ] as $m
        ) {
            Menu::query()->create([
                'parent_id' => $resource->id,
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
                ['name' => 'OpLog', 'title' => '操作日志', 'path' => '/monitor/operation-logs', 'permission' => 'system.operation_logs', 'sort' => 501, 'icon' => 'ep:document'],
                ['name' => 'LoginLog', 'title' => '登录日志', 'path' => '/monitor/login-logs', 'permission' => 'system.login_logs', 'sort' => 502, 'icon' => 'ep:notebook'],
                ['name' => 'ExceptionLog', 'title' => '异常日志', 'path' => '/monitor/exception-logs', 'permission' => 'system.exception_logs', 'sort' => 503, 'icon' => 'ep:warning-filled'],
                ['name' => 'OnlineUsers', 'title' => '在线用户', 'path' => '/monitor/online-users', 'permission' => 'system.online_users', 'sort' => 504, 'icon' => 'ep:view'],
                ['name' => 'ScheduledTasks', 'title' => '定时任务', 'path' => '/monitor/scheduled-tasks', 'permission' => 'system.scheduled_tasks', 'sort' => 505, 'icon' => 'ep:timer'],
            ] as $m
        ) {
            Menu::query()->create([
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
                ['name' => 'Codegen', 'title' => '代码生成', 'path' => '/dev/codegen', 'permission' => 'system.codegen', 'sort' => 601, 'icon' => 'ep:magic-stick'],
                ['name' => 'ModuleMgmt', 'title' => '模块管理', 'path' => '/dev/modules', 'permission' => 'system.modules', 'sort' => 602, 'icon' => 'ep:grid'],
                ['name' => 'ApiDocs', 'title' => '接口文档', 'path' => '/dev/api-docs', 'permission' => 'system.api_docs', 'sort' => 603, 'icon' => 'ep:link'],
            ] as $m
        ) {
            Menu::query()->create([
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

        Setting::query()->updateOrCreate(
            ['key' => 'app.page_size'],
            [
                'name' => '默认分页大小',
                'value' => '15',
                'group_name' => 'param',
                'type' => 'number',
                'sort' => 10,
                'remark' => '列表默认每页条数',
            ]
        );
    }

    private function seedDepartments(): Department
    {
        $root = Department::query()->updateOrCreate(
            ['parent_id' => 0, 'name' => '总公司'],
            [
                'leader' => '张总',
                'phone' => '010-88880000',
                'email' => 'ceo@starship.org',
                'sort' => 0,
                'status' => 1,
                'remark' => '集团总部',
            ],
        );

        $this->createDepartmentTree([
            [
                'name' => '研发中心',
                'leader' => '李研',
                'phone' => '010-88880001',
                'sort' => 10,
                'children' => [
                    ['name' => '前端组', 'leader' => '王前', 'sort' => 1],
                    ['name' => '后端组', 'leader' => '赵后', 'sort' => 2],
                    ['name' => '测试组', 'leader' => '钱测', 'sort' => 3],
                ],
            ],
            [
                'name' => '市场营销部',
                'leader' => '孙市',
                'phone' => '010-88880002',
                'sort' => 20,
            ],
            [
                'name' => '人力资源部',
                'leader' => '周人',
                'phone' => '010-88880003',
                'sort' => 30,
            ],
            [
                'name' => '财务部',
                'leader' => '吴财',
                'phone' => '010-88880004',
                'sort' => 40,
                'status' => 0,
                'remark' => '示例：禁用状态',
            ],
        ], (int) $root->id);

        return $root;
    }

    private function seedPosts(): void
    {
        $posts = [
            ['name' => '总经理', 'code' => 'ceo', 'sort' => 0, 'status' => 1, 'remark' => '公司最高管理者'],
            ['name' => '研发总监', 'code' => 'rd_director', 'sort' => 10, 'status' => 1, 'remark' => '研发中心负责人'],
            ['name' => '前端工程师', 'code' => 'frontend_engineer', 'sort' => 11, 'status' => 1, 'remark' => '对应前端组'],
            ['name' => '后端工程师', 'code' => 'backend_engineer', 'sort' => 12, 'status' => 1, 'remark' => '对应后端组'],
            ['name' => '测试工程师', 'code' => 'qa_engineer', 'sort' => 13, 'status' => 1, 'remark' => '对应测试组'],
            ['name' => '市场经理', 'code' => 'marketing_manager', 'sort' => 20, 'status' => 1, 'remark' => '市场营销部'],
            ['name' => '销售专员', 'code' => 'sales_specialist', 'sort' => 21, 'status' => 1, 'remark' => null],
            ['name' => '人事专员', 'code' => 'hr_specialist', 'sort' => 30, 'status' => 1, 'remark' => '人力资源部'],
            ['name' => '财务专员', 'code' => 'finance_specialist', 'sort' => 40, 'status' => 0, 'remark' => '示例：禁用状态'],
        ];

        foreach ($posts as $post) {
            Post::query()->updateOrCreate(
                ['code' => $post['code']],
                [
                    'name' => $post['name'],
                    'sort' => $post['sort'],
                    'status' => $post['status'],
                    'remark' => $post['remark'],
                ],
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function createDepartmentTree(array $nodes, int $parentId): void
    {
        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);

            $dept = Department::query()->updateOrCreate(
                ['parent_id' => $parentId, 'name' => (string) $node['name']],
                array_merge(
                    [
                        'leader' => null,
                        'phone' => null,
                        'email' => null,
                        'sort' => 0,
                        'status' => 1,
                        'remark' => null,
                    ],
                    $node,
                ),
            );

            if ($children !== []) {
                $this->createDepartmentTree($children, (int) $dept->id);
            }
        }
    }
}
