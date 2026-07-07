<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Generator\App\Models\GeneratorColumn;
use Modules\Generator\App\Models\GeneratorTable;
use Modules\Generator\App\Support\GeneratorNaming;

class TableConfigService
{
    public function __construct(
        protected SchemaReader $schemaReader,
        protected ColumnInferencer $columnInferencer,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function import(string $tableName, array $options = []): GeneratorTable
    {
        if (! $this->schemaReader->tableExists($tableName)) {
            throw new InvalidArgumentException("数据表不存在：{$tableName}");
        }

        $naming = GeneratorNaming::fromTableName(
            $tableName,
            isset($options['module']) ? (string) $options['module'] : null,
        );

        $businessName = (string) ($options['business'] ?? $this->schemaReader->tableComment($tableName) ?? $naming['class_name']);
        $routeGroup = (string) ($options['route_group'] ?? $naming['route_group']);
        $apiPrefix = (string) ($options['api_prefix'] ?? $naming['api_prefix']);
        $routePath = (string) ($options['route_path'] ?? $naming['route_path']);
        $permissionPrefix = (string) ($options['permission_prefix'] ?? $naming['permission_prefix']);
        $className = (string) ($options['class'] ?? $naming['class_name']);

        return DB::transaction(function () use (
            $tableName,
            $className,
            $naming,
            $businessName,
            $routeGroup,
            $routePath,
            $apiPrefix,
            $permissionPrefix,
            $options,
        ): GeneratorTable {
            $config = GeneratorTable::query()->updateOrCreate(
                ['table_name' => $tableName],
                [
                    'class_name' => $className,
                    'module_slug' => (string) ($options['module'] ?? $naming['module_slug']),
                    'business_name' => $businessName,
                    'route_group' => $routeGroup,
                    'route_path' => $routePath,
                    'api_prefix' => $apiPrefix,
                    'permission_prefix' => $permissionPrefix,
                    'menu_parent_path' => $options['menu_parent_path'] ?? null,
                    'template_type' => 'crud',
                    'options' => $this->buildTableOptions($tableName, $options),
                    'status' => 'draft',
                ],
            );

            $columns = $this->schemaReader->listColumns($tableName);
            $inferred = $this->columnInferencer->inferMany($columns);

            GeneratorColumn::query()->where('generator_table_id', $config->id)->delete();
            foreach ($inferred as $column) {
                GeneratorColumn::query()->create([
                    'generator_table_id' => $config->id,
                    ...$column,
                ]);
            }

            return $config->fresh(['columns']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(GeneratorTable $config, array $payload): GeneratorTable
    {
        $config->fill([
            'business_name' => $payload['business_name'] ?? $config->business_name,
            'module_slug' => $payload['module_slug'] ?? $config->module_slug,
            'route_group' => $payload['route_group'] ?? $config->route_group,
            'route_path' => $payload['route_path'] ?? $config->route_path,
            'api_prefix' => $payload['api_prefix'] ?? $config->api_prefix,
            'permission_prefix' => $payload['permission_prefix'] ?? $config->permission_prefix,
            'menu_parent_path' => $payload['menu_parent_path'] ?? $config->menu_parent_path,
            'options' => $payload['options'] ?? $config->options,
        ])->save();

        if (isset($payload['columns']) && is_array($payload['columns'])) {
            foreach ($payload['columns'] as $columnPayload) {
                if (! isset($columnPayload['id'])) {
                    continue;
                }
                GeneratorColumn::query()
                    ->where('generator_table_id', $config->id)
                    ->whereKey($columnPayload['id'])
                    ->update(collect($columnPayload)->except(['id'])->all());
            }
        }

        return $config->fresh(['columns']);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function buildTableOptions(string $tableName, array $options): array
    {
        $columns = collect($this->schemaReader->listColumns($tableName));
        $hasStatus = $columns->contains(fn (array $column): bool => $column['name'] === 'status');
        $hasSoftDelete = $columns->contains(fn (array $column): bool => $column['name'] === 'deleted_at');

        return [
            'soft_delete' => $options['soft_delete'] ?? $hasSoftDelete,
            'batch_delete' => $options['batch_delete'] ?? true,
            'batch_status' => $options['batch_status'] ?? $hasStatus,
            'keyword_search' => $options['keyword_search'] ?? true,
            'mobile' => $options['mobile'] ?? true,
            'sync_menu' => $options['sync_menu'] ?? true,
        ];
    }
}
