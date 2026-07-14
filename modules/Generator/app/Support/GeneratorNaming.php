<?php

namespace Modules\Generator\App\Support;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class GeneratorNaming
{
    /**
     * @return array{
     *   table_name: string,
     *   table_suffix: string,
     *   class_name: string,
     *   entity_kebab: string,
     *   api_prefix: string,
     *   permission_prefix: string,
     *   module_slug: string,
     *   route_group: string,
     *   route_path: string,
     *   component_path: string,
     *   variable_name: string,
     * }
     */
    public static function fromTableName(string $tableName, ?string $moduleSlug = null): array
    {
        $prefix = Schema::getConnection()->getTablePrefix();
        $withoutPrefix = Str::startsWith($tableName, $prefix)
            ? Str::after($tableName, $prefix)
            : $tableName;

        $parts = explode('_', $withoutPrefix);
        $modulePart = $parts[0] ?? 'system';
        $resolvedModule = $moduleSlug ?: Str::studly($modulePart);

        $entityParts = array_slice($parts, 1);
        if ($entityParts === []) {
            $entityParts = [$modulePart];
        }

        $entitySnake = implode('_', $entityParts);
        $className = Str::studly($entitySnake);
        $entityKebab = str_replace('_', '-', $entitySnake);
        $permissionEntity = Str::plural($entitySnake);
        $apiPrefix = $permissionEntity;
        $permissionPrefix = Str::lower($resolvedModule).'.'.$permissionEntity;
        $routeGroup = self::defaultRouteGroup($resolvedModule);
        $routePath = '/'.$routeGroup.'/'.str_replace('_', '-', $apiPrefix);
        $componentPath = '/'.$routeGroup.'/'.$entityKebab.'/index';

        return [
            'table_name' => $tableName,
            'table_suffix' => $withoutPrefix,
            'class_name' => $className,
            'entity_kebab' => $entityKebab,
            'api_prefix' => $apiPrefix,
            'permission_prefix' => $permissionPrefix,
            'module_slug' => $resolvedModule,
            'route_group' => $routeGroup,
            'route_path' => $routePath,
            'component_path' => $componentPath,
            'variable_name' => Str::camel($className),
        ];
    }

    public static function defaultRouteGroup(string $moduleSlug): string
    {
        return match (Str::lower($moduleSlug)) {
            'system' => 'org',
            'monitor' => 'monitor',
            'generator' => 'dev',
            default => Str::kebab($moduleSlug),
        };
    }

    /**
     * 管理端 API 路由前缀，约定与 module.json 的 name 及 routes/admin.php 的 prefix 一致。
     * 新增模块时保持 name 与 prefix 相同即可，无需改此处。
     */
    public static function defaultApiRoutePrefix(string $moduleSlug): string
    {
        return Str::kebab($moduleSlug);
    }

    public static function permissionsClassName(string $className): string
    {
        return $className.'Permissions';
    }

    public static function controllerClassName(string $className): string
    {
        return $className.'Controller';
    }
}
