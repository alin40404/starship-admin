<?php

namespace Modules\System\App\Services;

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

            $menus = $module->menus();
            $permissions = $module->permissions();
            $paths = array_merge($paths, $menus['paths']);
            $prefixes = array_merge($prefixes, $permissions['prefixes']);
            $exact = array_merge($exact, $permissions['exact']);
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
        $path = (string) ($menu->path ?? '');
        if ($path !== '' && in_array($path, $bindings['paths'], true)) {
            return true;
        }

        $permission = (string) ($menu->permission ?? '');
        if ($permission === '') {
            return false;
        }

        if (in_array($permission, $bindings['permissions']['exact'], true)) {
            return true;
        }

        foreach ($bindings['permissions']['prefixes'] as $prefix) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
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
