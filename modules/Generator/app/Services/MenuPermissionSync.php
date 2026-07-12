<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Generator\App\Models\GeneratorTable;
use Modules\Generator\App\Support\GeneratorNaming;
use Modules\Generator\App\Support\ModulePathResolver;
use Modules\System\App\Models\Menu;

class MenuPermissionSync
{
    public function __construct(
        protected ModulePathResolver $modulePathResolver,
    ) {}

    /**
     * @return list<array{path: string, action: string}>
     */
    public function sync(GeneratorTable $config): array
    {
        if (! ($config->options['sync_menu'] ?? true)) {
            return [];
        }

        $actions = [];
        $parent = $this->resolveParentMenu($config);
        $component = GeneratorNaming::fromTableName($config->table_name, $config->module_slug)['component_path'];
        if ($config->route_path) {
            $component = GeneratorNaming::fromTableName($config->table_name, $config->module_slug)['component_path'];
        }

        $menu = Menu::query()->firstOrCreate(
            ['path' => $config->route_path],
            [
                'parent_id' => $parent?->id ?? 0,
                'type' => 2,
                'name' => Str::studly($config->api_prefix).'Mgmt',
                'title' => $config->business_name,
                'component' => $component,
                'permission' => $config->permission_prefix,
                'icon' => 'ep:document',
                'sort' => 900,
                'other_settings' => Menu::normalizeOtherSettings(['keep_alive' => 1]),
                'status' => 1,
            ],
        );

        $menu->fill([
            'parent_id' => $parent?->id ?? $menu->parent_id,
            'title' => $config->business_name,
            'component' => $component,
            'permission' => $config->permission_prefix,
        ])->save();

        $actions[] = ['path' => 'menu:'.$menu->id, 'action' => 'synced'];

        $permissionClass = GeneratorNaming::permissionsClassName($config->class_name);
        $moduleNamespace = $this->modulePathResolver->moduleNamespace($config->module_slug);
        $definitionsClass = $moduleNamespace.'\\Support\\'.$permissionClass;

        if (class_exists($definitionsClass) && method_exists($definitionsClass, 'buttonDefinitions')) {
            foreach ($definitionsClass::buttonDefinitions() as $btn) {
                $button = Menu::query()->firstOrCreate(
                    [
                        'parent_id' => $menu->id,
                        'permission' => $btn['permission'],
                    ],
                    [
                        'type' => 3,
                        'name' => $btn['name'],
                        'title' => $btn['title'],
                        'path' => null,
                        'component' => null,
                        'icon' => null,
                        'sort' => $btn['sort'],
                        'other_settings' => Menu::normalizeOtherSettings(['hide_menu' => 1]),
                        'status' => 1,
                    ],
                );
                $actions[] = ['path' => 'menu:'.$button->id, 'action' => 'synced'];
            }
        }

        return $actions;
    }

    /**
     * @return list<array{path: string, action: string}>
     */
    public function remove(GeneratorTable $config): array
    {
        $actions = [];

        $pageMenu = Menu::query()->where('path', $config->route_path)->first();
        if ($pageMenu) {
            Menu::query()->where('parent_id', $pageMenu->id)->delete();
            $pageMenu->delete();
            $actions[] = ['path' => 'menu:'.$pageMenu->id, 'action' => 'deleted'];
        }

        $orphanButtons = Menu::query()
            ->where('permission', 'like', $config->permission_prefix.'.%')
            ->get();

        foreach ($orphanButtons as $button) {
            $button->delete();
            $actions[] = ['path' => 'menu:'.$button->id, 'action' => 'deleted'];
        }

        return $actions;
    }

    protected function resolveParentMenu(GeneratorTable $config): ?Menu
    {
        if ($config->menu_parent_path) {
            return Menu::query()->where('path', $config->menu_parent_path)->first();
        }

        $group = $config->route_group;
        $defaultParent = match ($group) {
            'org' => '/org',
            'platform' => '/platform',
            'resource' => '/resource',
            'permission' => '/permission',
            'monitor' => '/monitor',
            default => null,
        };

        if ($defaultParent === null) {
            return null;
        }

        return Menu::query()->where('path', $defaultParent)->first();
    }
}
