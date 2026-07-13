<?php

namespace Modules\Generator\App\Support;

final class RelationApiPresets
{
    /**
     * @return array{
     *   component: string,
     *   mode: string,
     *   api_route_prefix: string,
     *   api_entity: string,
     *   api_fn: string,
     *   label_field: string,
     *   mapper_fn?: string,
     *   extra_imports?: list<string>
     * }|null
     */
    public static function resolve(string $logicalTable): ?array
    {
        return match ($logicalTable) {
            'categories' => [
                'component' => 'ApiTreeSelect',
                'mode' => 'tree',
                'api_route_prefix' => 'system',
                'api_entity' => 'category',
                'api_fn' => 'fetchCategoryOptionsTree',
                'label_field' => 'name',
                'mapper_fn' => 'mapCategoryForTreeSelect',
                'extra_imports' => ['mapCategoryForTreeSelect'],
            ],
            'departments' => [
                'component' => 'ApiTreeSelect',
                'mode' => 'tree',
                'api_route_prefix' => 'system',
                'api_entity' => 'department',
                'api_fn' => 'fetchDepartmentOptionsTree',
                'label_field' => 'name',
                'mapper_fn' => 'mapDeptForTreeSelect',
                'extra_imports' => ['mapDeptForTreeSelect'],
            ],
            'admins' => [
                'component' => 'ApiSelect',
                'mode' => 'select',
                'api_route_prefix' => 'system',
                'api_entity' => 'admin',
                'api_fn' => 'fetchAdminPage',
                'label_field' => 'nickname',
            ],
            default => null,
        };
    }
}
