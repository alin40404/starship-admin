<?php

namespace Modules\System\App\Services;

use App\Support\StarshipTables;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Menu;

class AdminPermissionService
{
    /**
     * 当前管理员可访问的菜单行（含因子节点而补全的父级），status=1。
     */
    public function accessibleMenus(Admin $admin): Collection
    {
        if ((int) $admin->is_super === 1) {
            return Menu::query()
                ->where('status', 1)
                ->orderBy('sort')
                ->orderBy('id')
                ->get();
        }

        $roleIds = $admin->roles()->pluck('id');
        if ($roleIds->isEmpty()) {
            return collect();
        }

        $menuIds = collect(
            DB::table(StarshipTables::table('role_menu'))
                ->whereIn('role_id', $roleIds)
                ->pluck('menu_id')
        )->unique()->values();

        if ($menuIds->isEmpty()) {
            return collect();
        }

        $expanded = $this->expandMenuIdsWithAncestors($menuIds);

        return Menu::query()
            ->whereIn('id', $expanded)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
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

        return $this->buildTree($rows, 0);
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
