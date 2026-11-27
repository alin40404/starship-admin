<?php

namespace Modules\System\App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\ExceptionLog;
use Modules\System\App\Models\Menu;

class AdminPermissionService
{
    public function __construct(
        protected ModuleMenuFilter $moduleMenuFilter,
    ) {}

    /**
     * 当前管理员可访问的菜单行（含因子节点而补全的父级），status=1。
     */
    public function accessibleMenus(Admin $admin): Collection
    {
        if ((int) $admin->is_super === 1) {
            $menus = Menu::query()
                ->where('status', 1)
                ->orderBy('sort')
                ->orderBy('id')
                ->get();
        } else {
            $roleIds = $admin->roles()->pluck(
                $admin->roles()->getRelated()->getQualifiedKeyName(),
            );
            if ($roleIds->isEmpty()) {
                return collect();
            }

            $menuIds = collect(
                DB::table('role_menu')
                    ->whereIn('role_id', $roleIds)
                    ->pluck('menu_id')
            )->unique()->values();

            if ($menuIds->isEmpty()) {
                return collect();
            }

            $expanded = $this->expandMenuIdsWithAncestors($menuIds);

            $menus = Menu::query()
                ->whereIn('id', $expanded)
                ->where('status', 1)
                ->orderBy('sort')
                ->orderBy('id')
                ->get();
        }

        return $this->excludeDisabledModuleMenus($menus);
    }

    /**
     * 权限标识列表（菜单/按钮上配置的 permission），用于前端按钮显隐。
     *
     * @return list<string>
     */
    public function permissionCodes(Admin $admin): array
    {
        $menus = $this->accessibleMenus($admin);

        return $menus
            ->pluck('permission')
            ->filter(fn (?string $p) => $p !== null && $p !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(Admin $admin, string $permission): bool
    {
        if ((int) $admin->is_super === 1) {
            return true;
        }

        return in_array($permission, $this->permissionCodes($admin), true);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function hasAnyPermission(Admin $admin, array $permissions): bool
    {
        if ((int) $admin->is_super === 1) {
            return true;
        }

        $codes = array_flip($this->permissionCodes($admin));

        foreach ($permissions as $permission) {
            if (isset($codes[$permission])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 侧边栏用菜单树（仅 visible=1）。
     *
     * @return array<int, mixed>
     */
    public function menuTreeForAdmin(Admin $admin): array
    {
        $rows = $this->accessibleMenus($admin)->filter(
            fn (Menu $m) => (int) data_get($m->mergedOtherSettings(), 'hide_menu', 0) !== 1
        );

        return $this->applyExceptionLogUnreadBadge($this->buildTree($rows, 0));
    }

    /**
     * @param  array<int, mixed>  $tree
     * @return array<int, mixed>
     */
    private function applyExceptionLogUnreadBadge(array $tree): array
    {
        try {
            $unread = ExceptionLog::query()->where('is_read', 0)->count();
        } catch (\Throwable) {
            return $tree;
        }

        $badge = $unread > 0 ? ($unread > 99 ? '99+' : (string) $unread) : null;

        $walk = function (array &$nodes) use (&$walk, $badge): void {
            foreach ($nodes as &$node) {
                if (($node['path'] ?? null) === '/monitor/exception-logs') {
                    $node['badge'] = $badge;
                    $node['badge_type'] = $badge !== null ? 'normal' : null;
                    $node['badge_variants'] = $badge !== null ? 'destructive' : null;
                }
                if (! empty($node['children']) && is_array($node['children'])) {
                    $walk($node['children']);
                }
            }
            unset($node);
        };

        $walk($tree);

        return $tree;
    }

    /**
     * @param  Collection<int, Menu>  $rows
     * @return array<int, mixed>
     */
    private function buildTree(Collection $rows, int $parentId): array
    {
        $branch = [];

        foreach ($rows->where('parent_id', $parentId) as $item) {
            $os = Menu::expandOtherSettingsForApi($item);
            $branch[] = [
                'id' => $item->id,
                'parent_id' => $item->parent_id,
                'type' => $item->type,
                'name' => $item->name,
                'title' => $item->title,
                'path' => $item->path,
                'active_path' => $item->active_path,
                'component' => $item->component,
                'permission' => $item->permission,
                'icon' => $item->icon,
                'active_icon' => $item->active_icon,
                'badge_type' => $item->badge_type,
                'badge' => $item->badge,
                'badge_variants' => $item->badge_variants,
                'link_url' => $item->link_url,
                'iframe_src' => $item->iframe_src,
                'sort' => $item->sort,
                'visible' => $os['visible'],
                'keep_alive' => $os['keep_alive'],
                'affix_tab' => $os['affix_tab'],
                'status' => $item->status,
                'remark' => $item->remark,
                'hide_submenu' => $os['hide_submenu'],
                'hide_in_breadcrumb' => $os['hide_in_breadcrumb'],
                'hide_in_tab' => $os['hide_in_tab'],
                'children' => $this->buildTree($rows, (int) $item->id),
            ];
        }

        return $branch;
    }

    /**
     * @param  Collection<int, Menu>  $menus
     */
    private function excludeDisabledModuleMenus(Collection $menus): Collection
    {
        $excluded = $this->moduleMenuFilter->excludedMenuIds($menus);
        if ($excluded === []) {
            return $menus;
        }

        $excludedSet = array_fill_keys($excluded, true);

        return $menus
            ->filter(static fn (Menu $menu) => ! isset($excludedSet[(int) $menu->id]))
            ->values();
    }

    /**
     * @param  Collection<int, int|string>  $menuIds
     * @return list<int>
     */
    private function expandMenuIdsWithAncestors(Collection $menuIds): array
    {
        $ids = $menuIds->map(fn ($id) => (int) $id)->unique()->values();
        $stack = $ids->all();
        $seen = array_fill_keys($ids->all(), true);

        while ($stack !== []) {
            $id = (int) array_pop($stack);
            $parentId = (int) Menu::query()->whereKey($id)->value('parent_id');
            if ($parentId > 0 && ! isset($seen[$parentId])) {
                $seen[$parentId] = true;
                $stack[] = $parentId;
            }
        }

        return array_map('intval', array_keys($seen));
    }
}
