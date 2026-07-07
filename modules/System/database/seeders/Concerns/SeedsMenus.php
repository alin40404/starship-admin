<?php

namespace Modules\System\Database\Seeders\Concerns;

use Modules\System\App\Models\Menu;

trait SeedsMenus
{
    /**
     * @param  int  $legacyVisible  1=侧栏显示 0=侧栏隐藏（对应旧 visible）
     */
    protected function menuOther(int $legacyVisible, int $keepAlive = 0, int $affix = 0): array
    {
        return Menu::normalizeOtherSettings([
            'hide_menu' => $legacyVisible === 0 ? 1 : 0,
            'keep_alive' => $keepAlive,
            'affix_tab' => $affix,
        ]);
    }

    /**
     * 与 web-antd `router/routes` 中 componentKeys 一致：`/` 开头、无 `views/`、无 `.vue`。
     */
    protected function defaultComponentForMenuPath(?string $path): ?string
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
            '/platform/categories' => '/platform/category/index',
            '/platform/regions' => '/platform/region/index',
            '/resource/attachments' => '/resource/attachment/index',
            '/dev/codegen' => '/dev/codegen/index',
        ];
        if (isset($permission[$path])) {
            return $permission[$path];
        }

        return '/_core/fallback/coming-soon';
    }
}
