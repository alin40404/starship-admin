<?php

namespace Modules\System\App\Services;

use App\Support\Module\Module;
use App\Support\Module\ModuleManager;
use Illuminate\Support\Collection;
use Modules\System\App\Models\Menu;

class ModuleMenuFilter
{
    public function modulesPath(): string
    {
        return base_path('../modules');
    }

    /**
     * 属于已禁用模块、应从权限/菜单树中排除的菜单 ID（含子节点）。
     *
     * @return list<int>
     */
    public function excludedMenuIds(Collection $menus): array
    {
        $bindings = $this->disabledModuleMenuBindings();
        if ($this->bindingsAreEmpty($bindings)) {
            return [];
        }

        $excluded = collect();
        foreach ($menus as $menu) {
            if ($this->menuMatchesDisabledModule($menu, $bindings)) {
                $excluded->push((int) $menu->id);
            }
        }

        return $this->expandWithDescendants($menus, $excluded->unique()->values());
    }

    /**
     * 菜单管理页：解析菜单归属模块（按 module.json 的 menus / permissions 规则）。
     *
     * @return array<int, array{name: string, title: string, enabled: bool}>
     */
    public function menuModuleMap(Collection $menus): array
    {
        $map = [];
        $modules = (new ModuleManager($this->modulesPath()))->all();

        foreach ($menus as $menu) {
            $best = null;
            $bestScore = 0;

            foreach ($modules as $module) {
                $score = $this->moduleMatchScore($menu, $this->bindingsForModule($module));
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $module;
                }
            }

            if ($best === null) {
                continue;
            }

            $map[(int) $menu->id] = [
                'name' => $best->name(),
                'title' => $best->title(),
                'enabled' => $best->enabled(),
            ];
        }

        return $map;
    }

    /**
     * @return array{
     *   paths: list<string>,
     *   permissions: array{prefixes: list<string>, exact: list<string>}
     * }
     */
    private function bindingsForModule(Module $module): array
    {
        $menus = $module->menus();
        $permissions = $module->permissions();

        return [
            'paths' => $menus['paths'],
            'permissions' => [
                'prefixes' => $permissions['prefixes'],
                'exact' => $permissions['exact'],
            ],
        ];
    }

    /**
     * @return array{
     *   paths: list<string>,
     *   permissions: array{prefixes: list<string>, exact: list<string>}
     * }
     */
    private function disabledModuleMenuBindings(): array
    {
        $paths = [];
        $prefixes = [];
        $exact = [];

        foreach ((new ModuleManager($this->modulesPath()))->all() as $module) {
            if ($module->enabled()) {
                continue;
            }

            $bindings = $this->bindingsForModule($module);
            $paths = array_merge($paths, $bindings['paths']);
            $prefixes = array_merge($prefixes, $bindings['permissions']['prefixes']);
            $exact = array_merge($exact, $bindings['permissions']['exact']);
        }

        return [
            'paths' => array_values(array_unique($paths)),
            'permissions' => [
                'prefixes' => array_values(array_unique($prefixes)),
                'exact' => array_values(array_unique($exact)),
            ],
        ];
    }

    /**
     * @param  array{
     *   paths: list<string>,
     *   permissions: array{prefixes: list<string>, exact: list<string>}
     * }  $bindings
     */
    private function menuMatchesDisabledModule(Menu $menu, array $bindings): bool
    {
        return $this->moduleMatchScore($menu, $bindings) > 0;
    }

    /**
     * 匹配强度：path 精确 > permission 精确 > 最长 permission 前缀。
     *
     * @param  array{
     *   paths: list<string>,
     *   permissions: array{prefixes: list<string>, exact: list<string>}
     * }  $bindings
     */
    private function moduleMatchScore(Menu $menu, array $bindings): int
    {
        if ($this->bindingsAreEmpty($bindings)) {
            return 0;
        }

        $path = (string) ($menu->path ?? '');
        if ($path !== '' && in_array($path, $bindings['paths'], true)) {
            return 1_000_000 + strlen($path);
        }

        $permission = (string) ($menu->permission ?? '');
        if ($permission === '') {
            return 0;
        }

        if (in_array($permission, $bindings['permissions']['exact'], true)) {
            return 900_000 + strlen($permission);
        }

        $bestPrefixLength = 0;
        foreach ($bindings['permissions']['prefixes'] as $prefix) {
            if (str_starts_with($permission, $prefix)) {
                $bestPrefixLength = max($bestPrefixLength, strlen($prefix));
            }
        }

        return $bestPrefixLength;
    }

    /**
     * @param  array{
     *   paths: list<string>,
     *   permissions: array{prefixes: list<string>, exact: list<string>}
     * }  $bindings
     */
    private function bindingsAreEmpty(array $bindings): bool
    {
        return $bindings['paths'] === []
            && $bindings['permissions']['prefixes'] === []
            && $bindings['permissions']['exact'] === [];
    }

    /**
     * @param  Collection<int, Menu>  $menus
     * @param  Collection<int, int>  $seedIds
     * @return list<int>
     */
    private function expandWithDescendants(Collection $menus, Collection $seedIds): array
    {
        $excluded = $seedIds->map(static fn ($id) => (int) $id)->unique()->values();
        $byParent = $menus->groupBy(static fn (Menu $menu) => (int) $menu->parent_id);

        $stack = $excluded->all();
        while ($stack !== []) {
            $parentId = (int) array_pop($stack);
            foreach ($byParent->get($parentId, collect()) as $child) {
                $childId = (int) $child->id;
                if (! $excluded->contains($childId)) {
                    $excluded->push($childId);
                    $stack[] = $childId;
                }
            }
        }

        return $excluded->unique()->values()->all();
    }
}
