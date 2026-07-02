<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Area;
use Modules\System\App\Models\Department;
use Modules\System\App\Models\DictItem;
use Modules\System\App\Models\DictType;
use Modules\System\App\Models\Menu;
use Modules\System\App\Models\Post;
use Modules\System\App\Support\AdminPermissions;
use Modules\System\App\Support\AreaPermissions;
use Modules\System\App\Support\AttachmentPermissions;
use Modules\System\App\Support\ConfigPermissions;
use Modules\System\App\Support\DepartmentPermissions;
use Modules\System\App\Support\DictPermissions;
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

        Department::query()->whereKey($dept->id)->update(['leader_id' => $admin->id]);

        $this->seedDictBasics();
        $this->seedConfigBasics();
        $this->seedAreas();
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
            '/platform/configs' => '/platform/config/index',
            '/platform/dicts' => '/platform/dict/index',
            '/platform/regions' => '/platform/region/index',
            '/resource/attachments' => '/resource/attachment/index',
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

        $regionMgmt = Menu::query()->create([
            'parent_id' => $platform->id,
            'type' => 2,
            'name' => 'RegionMgmt',
            'title' => '区域管理',
            'path' => '/platform/regions',
            'component' => $this->defaultComponentForMenuPath('/platform/regions'),
            'permission' => 'system.regions',
            'icon' => 'ep:location',
            'sort' => 304,
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

        $attachmentCategory = DictType::query()->updateOrCreate(
            ['code' => 'attachment_category'],
            [
                'name' => '附件分类',
                'status' => 1,
                'remark' => '附件管理分类，参考 FastAdmin attachmentcategory',
            ]
        );

        $categories = [
            ['value' => 'default', 'label' => '默认', 'sort' => 0],
            ['value' => 'image', 'label' => '图片', 'sort' => 10],
            ['value' => 'video', 'label' => '视频', 'sort' => 20],
            ['value' => 'document', 'label' => '文档', 'sort' => 30],
            ['value' => 'avatar', 'label' => '头像', 'sort' => 40],
            ['value' => 'resources', 'label' => '资源', 'sort' => 50],
        ];

        foreach ($categories as $item) {
            DictItem::query()->updateOrCreate(
                [
                    'dict_type_id' => $attachmentCategory->id,
                    'value' => $item['value'],
                ],
                [
                    'label' => $item['label'],
                    'sort' => $item['sort'],
                    'status' => 1,
                ],
            );
        }
    }

    private function seedConfigBasics(): void
    {
        $siteConfigs = [
            [
                'key' => 'site.name',
                'name' => '系统名称',
                'value' => 'Starship Admin',
                'group_name' => 'basic',
                'type' => 'string',
                'sort' => 10,
                'remark' => '后台左上角及浏览器标题展示名称',
            ],
            [
                'key' => 'site.logo',
                'name' => '系统 Logo',
                'value' => '',
                'group_name' => 'basic',
                'type' => 'string',
                'sort' => 20,
                'remark' => '建议尺寸 128×128，留空则使用默认图标',
            ],
            [
                'key' => 'site.beian',
                'name' => '备案号',
                'value' => '',
                'group_name' => 'basic',
                'type' => 'string',
                'sort' => 30,
                'remark' => '网站 ICP 备案号，如：粤ICP备15000000号-1',
            ],
            [
                'key' => 'site.cdn_url',
                'name' => 'CDN 地址',
                'value' => '',
                'group_name' => 'basic',
                'type' => 'string',
                'sort' => 40,
                'remark' => '全站静态资源使用第三方 CDN 时填写',
            ],
            [
                'key' => 'site.version',
                'name' => '版本号',
                'value' => '1.0.1',
                'group_name' => 'basic',
                'type' => 'string',
                'sort' => 50,
                'remark' => '静态资源变更时可递增，用于缓存刷新',
            ],
            [
                'key' => 'site.timezone',
                'name' => '时区',
                'value' => 'Asia/Shanghai',
                'group_name' => 'basic',
                'type' => 'string',
                'sort' => 60,
                'remark' => 'PHP 时区标识，如 Asia/Shanghai',
            ],
            [
                'key' => 'site.forbidden_ip',
                'name' => '禁止 IP',
                'value' => '',
                'group_name' => 'basic',
                'type' => 'text',
                'sort' => 70,
                'remark' => '一行一条，匹配后将拒绝访问',
            ],
            [
                'key' => 'site.fixed_page',
                'name' => '默认主页',
                'value' => 'dashboard',
                'group_name' => 'basic',
                'type' => 'string',
                'sort' => 80,
                'remark' => '登录后默认打开的菜单路由',
            ],
            [
                'key' => 'sms.enabled',
                'name' => '启用短信',
                'value' => '0',
                'group_name' => 'sms',
                'type' => 'bool',
                'sort' => 10,
                'remark' => '关闭后系统将不发送短信通知',
            ],
            [
                'key' => 'sms.provider',
                'name' => '短信服务商',
                'value' => 'aliyun',
                'group_name' => 'sms',
                'type' => 'select',
                'sort' => 20,
                'remark' => '选择已接入的短信平台',
            ],
            [
                'key' => 'sms.access_key',
                'name' => 'AccessKey ID',
                'value' => '',
                'group_name' => 'sms',
                'type' => 'string',
                'sort' => 30,
                'remark' => '云厂商控制台获取的 AccessKey ID',
            ],
            [
                'key' => 'sms.secret_key',
                'name' => 'AccessKey Secret',
                'value' => '',
                'group_name' => 'sms',
                'type' => 'string',
                'sort' => 40,
                'remark' => '云厂商控制台获取的 AccessKey Secret',
            ],
            [
                'key' => 'sms.sign_name',
                'name' => '短信签名',
                'value' => '',
                'group_name' => 'sms',
                'type' => 'string',
                'sort' => 50,
                'remark' => '已在短信平台审核通过的签名名称',
            ],
            [
                'key' => 'sms.template_code',
                'name' => '默认模板 ID',
                'value' => '',
                'group_name' => 'sms',
                'type' => 'string',
                'sort' => 60,
                'remark' => '验证码等场景的默认短信模板编号',
            ],
            [
                'key' => 'sms.region',
                'name' => '接入地域',
                'value' => 'cn-hangzhou',
                'group_name' => 'sms',
                'type' => 'string',
                'sort' => 70,
                'remark' => '如 cn-hangzhou、ap-guangzhou',
            ],
            [
                'key' => 'inbox.enabled',
                'name' => '启用站内信',
                'value' => '1',
                'group_name' => 'inbox',
                'type' => 'bool',
                'sort' => 10,
                'remark' => '关闭后不再写入新的站内消息',
            ],
            [
                'key' => 'inbox.retention_days',
                'name' => '消息保留天数',
                'value' => '90',
                'group_name' => 'inbox',
                'type' => 'number',
                'sort' => 20,
                'remark' => '0 表示永久保留，过期消息将被清理',
            ],
            [
                'key' => 'inbox.page_size',
                'name' => '列表每页条数',
                'value' => '20',
                'group_name' => 'inbox',
                'type' => 'number',
                'sort' => 30,
                'remark' => '消息中心列表默认分页大小',
            ],
            [
                'key' => 'inbox.notify_system',
                'name' => '系统消息通知',
                'value' => '1',
                'group_name' => 'inbox',
                'type' => 'bool',
                'sort' => 40,
                'remark' => '是否允许发送系统类站内消息',
            ],
            [
                'key' => 'inbox.notify_todo',
                'name' => '待办任务通知',
                'value' => '1',
                'group_name' => 'inbox',
                'type' => 'bool',
                'sort' => 50,
                'remark' => '是否允许发送待办类站内消息',
            ],
            [
                'key' => 'inbox.notify_account',
                'name' => '账户安全通知',
                'value' => '1',
                'group_name' => 'inbox',
                'type' => 'bool',
                'sort' => 60,
                'remark' => '是否允许发送账户安全类站内消息',
            ],
            [
                'key' => 'mail.type',
                'name' => '邮件发送方式',
                'value' => '1',
                'group_name' => 'email',
                'type' => 'select',
                'sort' => 10,
                'remark' => '目前支持 SMTP',
            ],
            [
                'key' => 'mail.smtp_host',
                'name' => 'SMTP 服务器',
                'value' => 'smtp.qq.com',
                'group_name' => 'email',
                'type' => 'string',
                'sort' => 20,
                'remark' => '错误的配置可能导致邮件发送超时',
            ],
            [
                'key' => 'mail.smtp_port',
                'name' => 'SMTP 端口',
                'value' => '465',
                'group_name' => 'email',
                'type' => 'string',
                'sort' => 30,
                'remark' => '不加密默认 25，SSL 默认 465，TLS 默认 587',
            ],
            [
                'key' => 'mail.smtp_user',
                'name' => 'SMTP 用户名',
                'value' => '',
                'group_name' => 'email',
                'type' => 'string',
                'sort' => 40,
                'remark' => '填写完整邮箱账号',
            ],
            [
                'key' => 'mail.smtp_pass',
                'name' => 'SMTP 密码',
                'value' => '',
                'group_name' => 'email',
                'type' => 'string',
                'sort' => 50,
                'remark' => '邮箱密码或 SMTP 授权码',
            ],
            [
                'key' => 'mail.verify_type',
                'name' => 'SMTP 验证方式',
                'value' => '2',
                'group_name' => 'email',
                'type' => 'select',
                'sort' => 60,
                'remark' => '推荐使用 SSL',
            ],
            [
                'key' => 'mail.from',
                'name' => '发件人邮箱',
                'value' => '',
                'group_name' => 'email',
                'type' => 'string',
                'sort' => 70,
                'remark' => '系统邮件的发件地址',
            ],
            [
                'key' => 'app.storage.driver',
                'name' => '默认存储驱动',
                'value' => 'public',
                'group_name' => 'storage',
                'type' => 'select',
                'sort' => 10,
                'remark' => '附件上传默认使用的存储磁盘，公共存储可通过 /storage 访问',
            ],
            [
                'key' => 'app.upload.max_size',
                'name' => '上传大小上限',
                'value' => '20',
                'group_name' => 'storage',
                'type' => 'number',
                'sort' => 20,
                'remark' => '单文件上传最大体积（MB）',
            ],
            [
                'key' => 'app.upload.allowed_ext',
                'name' => '允许上传扩展名',
                'value' => '["jpg","jpeg","png","gif","webp","pdf","doc","docx","xls","xlsx","zip"]',
                'group_name' => 'storage',
                'type' => 'json',
                'sort' => 30,
                'remark' => 'JSON 数组，列出允许上传的文件扩展名',
            ],
            [
                'key' => 'storage.public_url',
                'name' => '公共访问地址',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 40,
                'remark' => '云存储驱动时使用；公共存储留空则使用 APP_URL/storage',
            ],
            [
                'key' => 'storage.oss.access_key',
                'name' => 'OSS AccessKey ID',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 110,
                'remark' => '选择阿里云 OSS 驱动时填写',
            ],
            [
                'key' => 'storage.oss.secret_key',
                'name' => 'OSS AccessKey Secret',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 120,
                'remark' => '选择阿里云 OSS 驱动时填写',
            ],
            [
                'key' => 'storage.oss.bucket',
                'name' => 'OSS Bucket',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 130,
                'remark' => '对象存储 Bucket 名称',
            ],
            [
                'key' => 'storage.oss.endpoint',
                'name' => 'OSS Endpoint',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 140,
                'remark' => '如 oss-cn-hangzhou.aliyuncs.com，留空则按地域自动生成',
            ],
            [
                'key' => 'storage.oss.region',
                'name' => 'OSS 地域',
                'value' => 'cn-hangzhou',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 150,
                'remark' => '如 cn-hangzhou、cn-beijing',
            ],
            [
                'key' => 'storage.cos.secret_id',
                'name' => 'COS SecretId',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 210,
                'remark' => '选择腾讯云 COS 驱动时填写',
            ],
            [
                'key' => 'storage.cos.secret_key',
                'name' => 'COS SecretKey',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 220,
                'remark' => '选择腾讯云 COS 驱动时填写',
            ],
            [
                'key' => 'storage.cos.bucket',
                'name' => 'COS Bucket',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 230,
                'remark' => '对象存储 Bucket 名称',
            ],
            [
                'key' => 'storage.cos.region',
                'name' => 'COS 地域',
                'value' => 'ap-guangzhou',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 240,
                'remark' => '如 ap-guangzhou、ap-shanghai',
            ],
            [
                'key' => 'storage.cos.endpoint',
                'name' => 'COS Endpoint',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 250,
                'remark' => '留空则按地域自动生成，如 cos.ap-guangzhou.myqcloud.com',
            ],
            [
                'key' => 'storage.s3.access_key',
                'name' => 'S3 Access Key',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 310,
                'remark' => '选择 AWS S3 或 S3 兼容存储时填写',
            ],
            [
                'key' => 'storage.s3.secret_key',
                'name' => 'S3 Secret Key',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 320,
                'remark' => '选择 AWS S3 或 S3 兼容存储时填写',
            ],
            [
                'key' => 'storage.s3.bucket',
                'name' => 'S3 Bucket',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 330,
                'remark' => '对象存储 Bucket 名称',
            ],
            [
                'key' => 'storage.s3.region',
                'name' => 'S3 地域',
                'value' => 'us-east-1',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 340,
                'remark' => 'AWS 区域标识，如 us-east-1、ap-southeast-1',
            ],
            [
                'key' => 'storage.s3.endpoint',
                'name' => 'S3 Endpoint',
                'value' => '',
                'group_name' => 'storage',
                'type' => 'string',
                'sort' => 350,
                'remark' => 'AWS 可留空；MinIO 等 S3 兼容存储填写完整地址',
            ],
        ];

        foreach ($siteConfigs as $config) {
            $key = $config['key'];
            unset($config['key']);
            Setting::query()->updateOrCreate(['key' => $key], $config);
        }

        Setting::query()->where('key', 'site.name')->update(['group_name' => 'basic']);

        $runtimeParams = [
            [
                'key' => 'app.page_size',
                'name' => '默认分页大小',
                'value' => '15',
                'group_name' => 'param',
                'type' => 'number',
                'sort' => 10,
                'remark' => '后台列表默认每页条数',
            ],
            [
                'key' => 'app.api.timeout',
                'name' => '接口超时时间',
                'value' => '30',
                'group_name' => 'param',
                'type' => 'number',
                'sort' => 20,
                'remark' => 'HTTP 接口请求超时时间（秒）',
            ],
            [
                'key' => 'app.jwt.ttl',
                'name' => '登录有效期',
                'value' => '120',
                'group_name' => 'param',
                'type' => 'number',
                'sort' => 30,
                'remark' => '管理员 Token 有效时长（分钟）',
            ],
            [
                'key' => 'app.login.captcha',
                'name' => '登录验证码',
                'value' => 'false',
                'group_name' => 'param',
                'type' => 'bool',
                'sort' => 40,
                'remark' => '是否在登录页启用图形验证码',
            ],
            [
                'key' => 'app.maintenance',
                'name' => '维护模式',
                'value' => 'false',
                'group_name' => 'param',
                'type' => 'bool',
                'sort' => 50,
                'remark' => '开启后普通管理员无法访问系统',
            ],
        ];

        foreach ($runtimeParams as $config) {
            $key = $config['key'];
            unset($config['key']);
            Setting::query()->updateOrCreate(['key' => $key], $config);
        }
    }

    private function seedDepartments(): Department
    {
        $root = Department::query()->updateOrCreate(
            ['parent_id' => 0, 'name' => '总公司'],
            [
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
                'phone' => '010-88880001',
                'sort' => 10,
                'children' => [
                    ['name' => '前端组', 'sort' => 1],
                    ['name' => '后端组', 'sort' => 2],
                    ['name' => '测试组', 'sort' => 3],
                ],
            ],
            [
                'name' => '市场营销部',
                'phone' => '010-88880002',
                'sort' => 20,
            ],
            [
                'name' => '人力资源部',
                'phone' => '010-88880003',
                'sort' => 30,
            ],
            [
                'name' => '财务部',
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
                        'leader_id' => null,
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

    private function seedAreas(): void
    {
        // 完整省市区请执行 database/sql/seed_areas.sql（3748 条 FastAdmin 数据）
        if (Area::query()->exists()) {
            return;
        }

        $rows = [
            ['id' => 1, 'pid' => 0, 'shortname' => '北京', 'name' => '北京市', 'mergename' => '中国,北京市', 'level' => 1, 'pinyin' => 'beijing', 'code' => '010', 'zip' => '100000', 'first' => 'B', 'lng' => '116.405285', 'lat' => '39.904989'],
            ['id' => 2, 'pid' => 0, 'shortname' => '浙江', 'name' => '浙江省', 'mergename' => '中国,浙江省', 'level' => 1, 'pinyin' => 'zhejiang', 'code' => '0571', 'zip' => '310000', 'first' => 'Z', 'lng' => '120.153576', 'lat' => '30.287459'],
            ['id' => 3, 'pid' => 0, 'shortname' => '广东', 'name' => '广东省', 'mergename' => '中国,广东省', 'level' => 1, 'pinyin' => 'guangdong', 'code' => '020', 'zip' => '510000', 'first' => 'G', 'lng' => '113.280637', 'lat' => '23.125178'],
            ['id' => 101, 'pid' => 2, 'shortname' => '杭州', 'name' => '杭州市', 'mergename' => '中国,浙江省,杭州市', 'level' => 2, 'pinyin' => 'hangzhou', 'code' => '0571', 'zip' => '310000', 'first' => 'H', 'lng' => '120.153576', 'lat' => '30.287459'],
            ['id' => 102, 'pid' => 1, 'shortname' => '东城', 'name' => '东城区', 'mergename' => '中国,北京市,东城区', 'level' => 3, 'pinyin' => 'dongcheng', 'code' => '010', 'zip' => '100010', 'first' => 'D', 'lng' => '116.418757', 'lat' => '39.917544'],
            ['id' => 103, 'pid' => 1, 'shortname' => '西城', 'name' => '西城区', 'mergename' => '中国,北京市,西城区', 'level' => 3, 'pinyin' => 'xicheng', 'code' => '010', 'zip' => '100032', 'first' => 'X', 'lng' => '116.366794', 'lat' => '39.915309'],
            ['id' => 201, 'pid' => 2, 'shortname' => '宁波', 'name' => '宁波市', 'mergename' => '中国,浙江省,宁波市', 'level' => 2, 'pinyin' => 'ningbo', 'code' => '0574', 'zip' => '315000', 'first' => 'N', 'lng' => '121.549792', 'lat' => '29.868388'],
            ['id' => 202, 'pid' => 101, 'shortname' => '西湖', 'name' => '西湖区', 'mergename' => '中国,浙江省,杭州市,西湖区', 'level' => 3, 'pinyin' => 'xihu', 'code' => '0571', 'zip' => '310013', 'first' => 'X', 'lng' => '120.130203', 'lat' => '30.259244'],
            ['id' => 301, 'pid' => 3, 'shortname' => '广州', 'name' => '广州市', 'mergename' => '中国,广东省,广州市', 'level' => 2, 'pinyin' => 'guangzhou', 'code' => '020', 'zip' => '510000', 'first' => 'G', 'lng' => '113.280637', 'lat' => '23.125178'],
            ['id' => 302, 'pid' => 3, 'shortname' => '深圳', 'name' => '深圳市', 'mergename' => '中国,广东省,深圳市', 'level' => 2, 'pinyin' => 'shenzhen', 'code' => '0755', 'zip' => '518000', 'first' => 'S', 'lng' => '114.085947', 'lat' => '22.547000'],
        ];

        foreach ($rows as $row) {
            Area::query()->create($row);
        }
    }
}
