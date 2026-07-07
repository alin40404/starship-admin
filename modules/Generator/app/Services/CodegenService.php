<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Str;
use Modules\Generator\App\Models\GeneratorTable;
use Modules\Generator\App\Support\GeneratorNaming;
use Modules\Generator\App\Support\ModulePathResolver;

class CodegenService
{
    public function __construct(
        protected TemplateRenderer $renderer,
        protected FileWriter $fileWriter,
        protected MenuPermissionSync $menuPermissionSync,
        protected GeneratorAuditLogger $auditLogger,
        protected ModulePathResolver $modulePathResolver,
        protected TableConfigService $tableConfigService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function preview(string $tableName, array $options = []): array
    {
        $config = $this->resolveConfig($tableName, $options);

        return $this->buildGenerationResult($config, $options, dryRun: true);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generate(string $tableName, array $options = []): array
    {
        $config = $this->resolveConfig($tableName, $options);
        $result = $this->buildGenerationResult($config, $options, dryRun: false);

        if (($options['sync_menu'] ?? $config->options['sync_menu'] ?? true) && empty($result['conflicts'])) {
            $result['menu'] = $this->menuPermissionSync->sync($config);
        }

        $config->update([
            'status' => 'generated',
            'generated_at' => now(),
        ]);

        $result['record'] = $this->auditLogger->log(
            $config,
            $result,
            source: (string) ($options['source'] ?? 'cli'),
            operatorId: isset($options['operator_id']) ? (int) $options['operator_id'] : null,
            operatorName: $options['operator_name'] ?? null,
            options: $options,
        );

        return $result;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function resolveConfig(string $tableName, array $options): GeneratorTable
    {
        $existing = GeneratorTable::query()->with('columns')->where('table_name', $tableName)->first();
        if ($existing) {
            return $existing;
        }

        return $this->tableConfigService->import($tableName, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function buildGenerationResult(GeneratorTable $config, array $options, bool $dryRun): array
    {
        $config->loadMissing('columns');
        $viewData = $this->buildViewData($config);
        $files = $this->buildFiles($config, $viewData);
        $force = (bool) ($options['force'] ?? false);

        if ($dryRun) {
            return [
                'config' => $config,
                'files' => collect($files)->map(fn (array $file): array => [
                    'path' => $file['path'],
                    'content' => $file['content'],
                ])->values()->all(),
                'written' => [],
                'skipped' => [],
                'conflicts' => collect($files)
                    ->filter(fn (array $file): bool => is_file($file['path']))
                    ->map(fn (array $file): array => ['path' => $file['path']])
                    ->values()
                    ->all(),
            ];
        }

        $writeResult = $this->fileWriter->writeMany($files, $force);

        $routeFile = $this->modulePathResolver
            ->resolveModuleDirectory($config->module_slug)
            .'/routes/admin.php';
        $routeSnippet = $this->renderer->render('backend/routes.snippet.stub', $viewData);

        if ($force || ! is_file($routeFile) || ! str_contains((string) file_get_contents($routeFile), '@generator:'.$viewData['api_prefix'].':start')) {
            if (! $dryRun) {
                $writeResult['written'][] = $this->fileWriter->appendRouteSnippet(
                    $routeFile,
                    $viewData['api_prefix'],
                    $routeSnippet,
                );
            }
        } else {
            $writeResult['skipped'][] = [
                'path' => $routeFile,
                'reason' => 'route_exists',
            ];
        }

        return array_merge($writeResult, [
            'config' => $config,
            'files' => collect($files)->map(fn (array $file): array => [
                'path' => $file['path'],
                'content' => $file['content'],
            ])->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildViewData(GeneratorTable $config): array
    {
        $naming = GeneratorNaming::fromTableName($config->table_name, $config->module_slug);
        $moduleNamespace = $this->modulePathResolver->moduleNamespace($config->module_slug);
        $columns = $config->columns;
        $formColumns = $columns->where('is_form', true)->values();
        $listColumns = $columns->where('is_list', true)->values();
        $searchColumns = $columns->where('is_search', true)->values();
        $keywordFields = $searchColumns->pluck('column_name')->filter(fn (string $name): bool => in_array($name, ['name', 'title', 'code'], true))->values()->all();
        if ($keywordFields === []) {
            $keywordFields = $searchColumns->take(2)->pluck('column_name')->all();
        }

        $hasStatus = $columns->contains(fn ($column): bool => $column->column_name === 'status');
        $hasSoftDelete = (bool) ($config->options['soft_delete'] ?? false);
        $filteredSearchColumns = $searchColumns
            ->filter(fn ($column): bool => ! in_array($column->column_name, $keywordFields, true))
            ->values();

        return [
            'config' => $config,
            'module_namespace' => $moduleNamespace,
            'model_class' => $config->class_name,
            'controller_class' => GeneratorNaming::controllerClassName($config->class_name),
            'permissions_class' => GeneratorNaming::permissionsClassName($config->class_name),
            'table_suffix' => $naming['table_suffix'],
            'business_name' => $config->business_name,
            'api_prefix' => $config->api_prefix,
            'permission_prefix' => $config->permission_prefix,
            'permission_const_prefix' => strtoupper(str_replace(['.', '-'], '_', $config->permission_prefix)),
            'permissions_export_name' => strtoupper(str_replace(['.', '-'], '_', $config->permission_prefix)).'_PERMISSIONS',
            'batch_delete_fn_name' => 'batchDelete'.Str::plural($config->class_name),
            'controller_fqcn' => $moduleNamespace.'\\Http\\Controllers\\Admin\\'.GeneratorNaming::controllerClassName($config->class_name),
            'route_group' => $config->route_group,
            'entity_kebab' => $naming['entity_kebab'],
            'route_path' => $config->route_path,
            'component_path' => $naming['component_path'],
            'variable_name' => $naming['variable_name'],
            'form_columns' => $formColumns,
            'list_columns' => $listColumns,
            'search_columns' => $searchColumns,
            'filtered_search_columns' => $filteredSearchColumns,
            'keyword_fields' => $keywordFields,
            'keyword_fields_ts' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", $keywordFields)),
            'form_rules_body' => $this->buildFormRulesBody($formColumns),
            'allowed_sorts_body' => $this->buildAllowedSortsBody($listColumns),
            'search_filters_body' => $this->buildSearchFiltersBody($filteredSearchColumns),
            'grid_form_schema_body' => $this->buildGridFormSchemaBody($searchColumns),
            'list_columns_body' => $this->buildListColumnsBody($listColumns, $config->class_name, strtoupper(str_replace(['.', '-'], '_', $config->permission_prefix)).'_PERMISSIONS'),
            'primary_label_field' => $listColumns->first()?->column_name ?? 'id',
            'has_status' => $hasStatus,
            'has_soft_delete' => $hasSoftDelete,
            'has_batch_delete' => (bool) ($config->options['batch_delete'] ?? true),
            'has_batch_status' => (bool) ($config->options['batch_status'] ?? $hasStatus),
            'has_keyword_search' => (bool) ($config->options['keyword_search'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $viewData
     * @return list<array{path: string, content: string}>
     */
    protected function buildFiles(GeneratorTable $config, array $viewData): array
    {
        $moduleDir = $this->modulePathResolver->resolveModuleDirectory($config->module_slug);
        $frontendRoot = $this->modulePathResolver->frontendRoot();

        $files = [
            [
                'path' => $moduleDir.'/app/Models/'.$viewData['model_class'].'.php',
                'content' => $this->renderer->render('backend/model.stub', $viewData),
            ],
            [
                'path' => $moduleDir.'/app/Http/Controllers/Admin/'.$viewData['controller_class'].'.php',
                'content' => $this->renderer->render('backend/controller.stub', $viewData),
            ],
            [
                'path' => $moduleDir.'/app/Support/'.$viewData['permissions_class'].'.php',
                'content' => $this->renderer->render('backend/permissions.stub', $viewData),
            ],
            [
                'path' => $frontendRoot.'/api/system/'.$viewData['entity_kebab'].'.ts',
                'content' => $this->renderer->render('frontend/api.stub', $viewData),
            ],
            [
                'path' => $frontendRoot.'/constants/'.$viewData['entity_kebab'].'-permissions.ts',
                'content' => $this->renderer->render('frontend/permissions.ts.stub', $viewData),
            ],
            [
                'path' => $frontendRoot.'/views/'.$config->route_group.'/'.$viewData['entity_kebab'].'/index.vue',
                'content' => $this->renderer->render('frontend/index.vue.stub', $viewData),
            ],
            [
                'path' => $frontendRoot.'/views/'.$config->route_group.'/'.$viewData['entity_kebab'].'/data.ts',
                'content' => $this->renderer->render('frontend/data.ts.stub', $viewData),
            ],
            [
                'path' => $frontendRoot.'/views/'.$config->route_group.'/'.$viewData['entity_kebab'].'/modules/form.vue',
                'content' => $this->renderer->render('frontend/form.vue.stub', $viewData),
            ],
        ];

        return $files;
    }

    protected function buildFormRulesBody($formColumns): string
    {
        $lines = [];
        foreach ($formColumns as $column) {
            $rules = collect($column->validation_rules ?? [])
                ->map(static fn (string $rule): string => "'{$rule}'")
                ->implode(', ');
            $lines[] = "            '{$column->column_name}' => [{$rules}],";
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildAllowedSortsBody($listColumns): string
    {
        $lines = [];
        foreach ($listColumns as $column) {
            $lines[] = "            '{$column->column_name}' => '{$column->column_name}',";
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildSearchFiltersBody($searchColumns): string
    {
        $lines = [];
        foreach ($searchColumns as $column) {
            if ($column->ts_type === 'number') {
                $lines[] = "        if (\$request->filled('{$column->column_name}')) {";
                $lines[] = "            \$query->where('{$column->column_name}', (int) \$request->get('{$column->column_name}'));";
                $lines[] = '        }';
            } else {
                $lines[] = "        if (\$request->filled('{$column->column_name}')) {";
                $lines[] = "            \$query->where('{$column->column_name}', 'like', '%'.\$request->get('{$column->column_name}').'%');";
                $lines[] = '        }';
            }
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildGridFormSchemaBody($searchColumns): string
    {
        $lines = [];
        foreach ($searchColumns as $column) {
            $component = $column->form_component === 'Switch' ? 'Select' : $column->form_component;
            $lines[] = '    {';
            $lines[] = "      component: '{$component}',";
            $lines[] = "      componentProps: { class: 'w-full', placeholder: '{$column->column_comment}' },";
            $lines[] = "      fieldName: '{$column->column_name}',";
            $lines[] = "      label: '{$column->column_comment}',";
            $lines[] = '    },';
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildListColumnsBody($listColumns, string $modelClass, string $permissionsExportName): string
    {
        $lines = [];
        foreach ($listColumns as $column) {
            if ($column->column_name === 'status') {
                $lines[] = "    {";
                $lines[] = "      field: 'status',";
                $lines[] = "      sortable: true,";
                $lines[] = "      title: '{$column->column_comment}',";
                $lines[] = '      width: 100,';
                $lines[] = '      cellRender: {';
                $lines[] = '        attrs: {';
                $lines[] = "          async beforeChange(newVal: unknown, row: {$modelClass}Row) {";
                $lines[] = "            return onStatusBeforeChange(newVal as number, row);";
                $lines[] = '          },';
                $lines[] = '          getSwitchProps: () => ({ disabled: !canUpdate }),';
                $lines[] = '        },';
                $lines[] = "        name: 'CellSwitch',";
                $lines[] = '      },';
                $lines[] = '    },';

                continue;
            }

            if (str_contains($column->column_type, 'datetime') || str_contains($column->column_type, 'timestamp')) {
                $lines[] = '    {';
                $lines[] = "      field: '{$column->column_name}',";
                $lines[] = "      formatter: ({ row }: { row: {$modelClass}Row }) =>";
                $lines[] = "        row.{$column->column_name} ? formatDateTime(row.{$column->column_name}) : TABLE_CELL_EMPTY,";
                $lines[] = '      minWidth: 170,';
                $lines[] = '      sortable: true,';
                $lines[] = "      title: '{$column->column_comment}',";
                $lines[] = '    },';

                continue;
            }

            $lines[] = '    {';
            $lines[] = "      align: 'left',";
            $lines[] = "      field: '{$column->column_name}',";
            $lines[] = '      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,';
            $lines[] = '      minWidth: 120,';
            $lines[] = '      sortable: true,';
            $lines[] = "      title: '{$column->column_comment}',";
            $lines[] = '    },';
        }

        return implode(PHP_EOL, $lines);
    }
}
