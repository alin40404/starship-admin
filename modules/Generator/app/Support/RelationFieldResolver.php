<?php

namespace Modules\Generator\App\Support;

use Illuminate\Support\Str;
use Modules\Generator\App\Models\GeneratorTable;
use Modules\Generator\App\Services\SchemaReader;

final class RelationFieldResolver
{
    /** @var list<string> */
    protected const LABEL_FIELD_CANDIDATES = [
        'name', 'title', 'nickname', 'username', 'label', 'code',
    ];

    /** @var callable(string): (?GeneratorTable)|null */
    protected $configResolver;

    public function __construct(
        protected SchemaReader $schemaReader,
        ?callable $configResolver = null,
    ) {
        $this->configResolver = $configResolver ?? static function (string $logicalTable): ?GeneratorTable {
            return GeneratorTable::query()->where('table_name', $logicalTable)->first();
        };
    }

  /**
   * @param  list<array{column: string, referenced_table: string, referenced_column: string}>  $foreignKeys
   * @return array{
   *   component: string,
   *   mode: string,
   *   related_table: string,
   *   label_field: string,
   *   value_field: string,
   *   api_route_prefix: string,
   *   api_entity: string,
   *   api_fn: string,
   *   related_class: string,
   *   loader_fn: string,
   * }|null
   */
    public function resolve(
        string $columnName,
        string $tableName,
        array $foreignKeys = [],
    ): ?array {
        if ($columnName === 'id' || ! str_ends_with($columnName, '_id')) {
            return null;
        }

        $referencedTable = $this->resolveReferencedTable($columnName, $tableName, $foreignKeys);
        if ($referencedTable === null) {
            return null;
        }

        $logicalTable = $this->schemaReader->logicalTableName($referencedTable);
        $relatedConfig = ($this->configResolver)($logicalTable);
        $preset = RelationApiPresets::resolve($logicalTable);
        $labelField = $preset['label_field'] ?? $this->inferLabelField($logicalTable);
        $relatedNaming = GeneratorNaming::fromTableName(
            $logicalTable,
            $relatedConfig?->module_slug,
        );
        $relatedClass = $relatedConfig?->class_name ?? $relatedNaming['class_name'];
        $apiRoutePrefix = $preset['api_route_prefix']
            ?? GeneratorNaming::defaultApiRoutePrefix($relatedConfig?->module_slug ?? $relatedNaming['module_slug']);
        $apiEntity = $preset['api_entity']
            ?? ($relatedConfig ? Str::kebab($relatedConfig->api_prefix) : $relatedNaming['entity_kebab']);
        $apiFn = $preset['api_fn'] ?? 'fetch'.$relatedClass.'Page';
        $loaderFn = 'load'.Str::studly($columnName).'Options';
        $isSelfTree = $columnName === 'parent_id'
            && $logicalTable === $this->schemaReader->logicalTableName($tableName);
        $component = $isSelfTree
            ? 'ApiTreeSelect'
            : ($preset['component'] ?? 'ApiSelect');
        $mode = $isSelfTree ? 'tree' : ($preset['mode'] ?? 'select');

        $result = [
            'component' => $component,
            'mode' => $mode,
            'related_table' => $logicalTable,
            'label_field' => $labelField,
            'value_field' => 'id',
            'api_route_prefix' => $apiRoutePrefix,
            'api_entity' => $apiEntity,
            'api_fn' => $apiFn,
            'related_class' => $relatedClass,
            'loader_fn' => $loaderFn,
        ];

        if ($preset !== null) {
            if (isset($preset['mapper_fn'])) {
                $result['mapper_fn'] = $preset['mapper_fn'];
            }
            if (isset($preset['extra_imports'])) {
                $result['extra_imports'] = $preset['extra_imports'];
            }
        }

        return $result;
    }

    /**
     * @param  list<array{column: string, referenced_table: string, referenced_column: string}>  $foreignKeys
     */
    protected function resolveReferencedTable(string $columnName, string $tableName, array $foreignKeys): ?string
    {
        foreach ($foreignKeys as $foreignKey) {
            if (($foreignKey['column'] ?? '') === $columnName) {
                return (string) $foreignKey['referenced_table'];
            }
        }

        $base = Str::beforeLast($columnName, '_id');
        if ($base === '') {
            return null;
        }

        if ($columnName === 'parent_id') {
            return $this->schemaReader->physicalTableName($tableName);
        }

        $candidates = array_values(array_unique([
            Str::plural($base),
            $base,
            Str::plural(Str::snake($base)),
        ]));

        foreach ($candidates as $candidate) {
            if ($this->schemaReader->tableExists($candidate)) {
                return $this->schemaReader->physicalTableName($candidate);
            }
        }

        return null;
    }

    protected function inferLabelField(string $logicalTable): string
    {
        if (! $this->schemaReader->tableExists($logicalTable)) {
            return 'name';
        }

        $columns = collect($this->schemaReader->listColumns($logicalTable));
        foreach (self::LABEL_FIELD_CANDIDATES as $candidate) {
            if ($columns->contains(fn (array $column): bool => $column['name'] === $candidate)) {
                return $candidate;
            }
        }

        return 'id';
    }
}
