<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Generator\App\Models\GeneratorRecord;
use Modules\Generator\App\Models\GeneratorTable;
use Modules\Generator\App\Support\ColumnLabelFormatter;
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
        protected SchemaReader $schemaReader,
        protected LocaleJsonMerger $localeJsonMerger,
        protected ModuleLangEnsurer $moduleLangEnsurer,
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
     * @return array<string, mixed>
     */
    public function previewDelete(string $tableName, array $options = []): array
    {
        return $this->buildDeleteResult($tableName, $options, dryRun: true);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function delete(string $tableName, array $options = []): array
    {
        return $this->buildDeleteResult($tableName, $options, dryRun: false);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function resolveConfig(string $tableName, array $options): GeneratorTable
    {
        $tableName = $this->schemaReader->resolveTableName($tableName);
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
    protected function buildDeleteResult(string $tableName, array $options, bool $dryRun): array
    {
        $tableName = $this->schemaReader->resolveTableName($tableName);
        [$config, $persisted] = $this->resolveDeleteConfig($tableName, $options);
        $files = $this->buildFiles($config, $this->buildViewData($config));
        $paths = array_values(array_unique(array_map(
            static fn (array $file): string => $file['path'],
            $files,
        )));

        $existingPaths = array_values(array_filter($paths, static fn (string $path): bool => is_file($path)));
        $force = (bool) ($options['force'] ?? false);
        $syncMenu = (bool) ($options['sync_menu'] ?? true);
        $dropTable = (bool) ($options['drop_table'] ?? false);

        if ($dryRun) {
            return [
                'config' => $config,
                'paths' => $paths,
                'existing' => $existingPaths,
                'missing' => array_values(array_diff($paths, $existingPaths)),
                'persisted' => $persisted,
                'sync_menu' => $syncMenu,
                'drop_table' => $dropTable,
            ];
        }

        $fileResult = $this->fileWriter->deleteMany($paths, $force);
        $menu = $syncMenu ? $this->menuPermissionSync->remove($config) : [];

        if ($dropTable) {
            $this->dropPhysicalTable($config);
        }

        if ($persisted) {
            $this->deleteGeneratorConfig($config);
        }

        return array_merge($fileResult, [
            'config' => $config,
            'menu' => $menu,
            'table_dropped' => $dropTable,
            'config_deleted' => $persisted,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{0: GeneratorTable, 1: bool}
     */
    protected function resolveDeleteConfig(string $tableName, array $options): array
    {
        $config = $this->findGeneratorConfig($tableName);
        if ($config) {
            $config->loadMissing('columns');

            return [$config, true];
        }

        return [$this->buildEphemeralConfig($tableName, $options), false];
    }

    protected function findGeneratorConfig(string $tableName): ?GeneratorTable
    {
        $resolved = $this->schemaReader->resolveTableName($tableName);
        $candidates = array_values(array_unique([$tableName, $resolved]));

        return GeneratorTable::query()
            ->with('columns')
            ->whereIn('table_name', $candidates)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function buildEphemeralConfig(string $tableName, array $options): GeneratorTable
    {
        $naming = GeneratorNaming::fromTableName(
            $tableName,
            isset($options['module']) ? (string) $options['module'] : null,
        );

        $moduleSlug = (string) ($options['module'] ?? $naming['module_slug']);
        $routeGroup = (string) ($options['route_group'] ?? GeneratorNaming::defaultRouteGroup($moduleSlug));
        $apiPrefix = (string) ($options['api_prefix'] ?? $naming['api_prefix']);
        $routePath = (string) ($options['route_path'] ?? '/'.$routeGroup.'/'.str_replace('_', '-', $apiPrefix));
        $permissionPrefix = (string) ($options['permission_prefix'] ?? strtolower($moduleSlug).'.'.$apiPrefix);

        $config = new GeneratorTable([
            'table_name' => $tableName,
            'class_name' => (string) ($options['class'] ?? $naming['class_name']),
            'module_slug' => $moduleSlug,
            'business_name' => (string) ($options['business'] ?? $naming['class_name']),
            'route_group' => $routeGroup,
            'route_path' => $routePath,
            'api_prefix' => $apiPrefix,
            'permission_prefix' => $permissionPrefix,
            'menu_parent_path' => $options['menu_parent_path'] ?? null,
            'options' => ['sync_menu' => $options['sync_menu'] ?? true],
        ]);
        $config->setRelation('columns', collect());

        return $config;
    }

    protected function deleteGeneratorConfig(GeneratorTable $config): void
    {
        GeneratorRecord::query()->where('generator_table_id', $config->id)->delete();
        $config->columns()->delete();
        $config->delete();
    }

    protected function dropPhysicalTable(GeneratorTable $config): void
    {
        $naming = GeneratorNaming::fromTableName($config->table_name, $config->module_slug);
        Schema::dropIfExists($naming['table_suffix']);
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
        $moduleDir = $this->modulePathResolver->resolveModuleDirectory($config->module_slug);

        $this->fileWriter->assertAdminRouteLoaderRegistered($moduleDir.'/routes/admin.php');

        if ($dryRun) {
            $localeFiles = $this->buildLocaleFiles($config, $viewData);
            $allFiles = array_merge($files, $localeFiles);

            return [
                'config' => $config,
                'files' => collect($allFiles)->map(fn (array $file): array => [
                    'path' => $file['path'],
                    'content' => $file['content'],
                ])->values()->all(),
                'written' => [],
                'skipped' => [],
                'conflicts' => collect($allFiles)
                    ->filter(fn (array $file): bool => is_file($file['path']))
                    ->map(fn (array $file): array => ['path' => $file['path']])
                    ->values()
                    ->all(),
            ];
        }

        $this->fileWriter->ensureAdminRouteLoader($moduleDir);
        $this->moduleLangEnsurer->ensure($config->module_slug);

        $writeResult = $this->fileWriter->writeMany($files, $force);
        $localeFiles = $this->buildLocaleFiles($config, $viewData);
        $localeWriteResult = $this->fileWriter->writeMany($localeFiles, true);
        $writeResult['written'] = array_merge($writeResult['written'], $localeWriteResult['written']);
        $writeResult['skipped'] = array_merge($writeResult['skipped'], $localeWriteResult['skipped']);

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
        $translationNs = strtolower($config->module_slug);
        $entityKebab = $naming['entity_kebab'];
        $i18nPrefix = $translationNs.'.'.$entityKebab;
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

        $apiRoutePrefix = GeneratorNaming::defaultApiRoutePrefix($config->module_slug);

        return [
            'config' => $config,
            'api_route_prefix' => $apiRoutePrefix,
            'module_namespace' => $moduleNamespace,
            'model_class' => $config->class_name,
            'controller_class' => GeneratorNaming::controllerClassName($config->class_name),
            'permissions_class' => GeneratorNaming::permissionsClassName($config->class_name),
            'table_suffix' => $naming['table_suffix'],
            'route_marker' => $naming['table_suffix'],
            'table_name' => $config->table_name,
            'business_name' => $config->business_name,
            'api_prefix' => $config->api_prefix,
            'permission_prefix' => $config->permission_prefix,
            'permission_const_prefix' => strtoupper(str_replace(['.', '-'], '_', $config->permission_prefix)),
            'permissions_export_name' => strtoupper(str_replace(['.', '-'], '_', $config->permission_prefix)).'_PERMISSIONS',
            'batch_delete_fn_name' => 'batchDelete'.Str::plural($config->class_name),
            'controller_fqcn' => $moduleNamespace.'\\Http\\Controllers\\Admin\\'.GeneratorNaming::controllerClassName($config->class_name),
            'route_group' => $config->route_group,
            'entity_kebab' => $entityKebab,
            'route_path' => $config->route_path,
            'component_path' => $naming['component_path'],
            'variable_name' => $naming['variable_name'],
            'translation_ns' => $translationNs,
            'i18n_prefix' => $i18nPrefix,
            'entity_title_en' => $config->class_name,
            'form_columns' => $formColumns,
            'list_columns' => $listColumns,
            'search_columns' => $searchColumns,
            'filtered_search_columns' => $filteredSearchColumns,
            'keyword_fields' => $keywordFields,
            'keyword_fields_ts' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", $keywordFields)),
            'form_rules_body' => $this->buildFormRulesBody($formColumns),
            'allowed_sorts_body' => $this->buildAllowedSortsBody($listColumns),
            'search_filters_body' => $this->buildSearchFiltersBody($filteredSearchColumns),
            'grid_form_schema_body' => $this->buildGridFormSchemaBody($searchColumns, $i18nPrefix),
            'form_schema_body' => $this->buildFormSchemaBody($formColumns, $i18nPrefix),
            'api_row_fields_body' => $this->buildApiRowFieldsBody($listColumns),
            'api_page_query_fields_body' => $this->buildApiPageQueryFieldsBody($searchColumns),
            'api_save_payload_fields_body' => $this->buildApiSavePayloadFieldsBody($formColumns),
            'index_query_fields_body' => $this->buildIndexQueryFieldsBody($searchColumns),
            'list_columns_body' => $this->buildListColumnsBody($listColumns, $config->class_name, strtoupper(str_replace(['.', '-'], '_', $config->permission_prefix)).'_PERMISSIONS', $i18nPrefix),
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
                'path' => $moduleDir.'/routes/admin-generator/'.$viewData['route_marker'].'.php',
                'content' => $this->normalizeGeneratedRouteContent(
                    $this->renderer->render('backend/routes.generated.stub', $viewData),
                ),
            ],
            [
                'path' => $frontendRoot.'/api/'.$viewData['api_route_prefix'].'/'.$viewData['entity_kebab'].'.ts',
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

    /**
     * @param  array<string, mixed>  $viewData
     * @return list<array{path: string, content: string}>
     */
    protected function buildLocaleFiles(GeneratorTable $config, array $viewData): array
    {
        $frontendRoot = $this->modulePathResolver->frontendRoot();
        $moduleSlug = strtolower($config->module_slug);
        $entityKey = (string) $viewData['entity_kebab'];
        $entries = $this->buildLocaleEntityEntries($config, $viewData);

        return array_merge(
            [
                [
                    'path' => $frontendRoot.'/locales/langs/zh-CN/'.$moduleSlug.'.json',
                    'content' => $this->localeJsonMerger->mergeEntity(
                        $frontendRoot.'/locales/langs/zh-CN/'.$moduleSlug.'.json',
                        $entityKey,
                        $entries['zh-CN'],
                    ),
                ],
                [
                    'path' => $frontendRoot.'/locales/langs/en-US/'.$moduleSlug.'.json',
                    'content' => $this->localeJsonMerger->mergeEntity(
                        $frontendRoot.'/locales/langs/en-US/'.$moduleSlug.'.json',
                        $entityKey,
                        $entries['en-US'],
                    ),
                ],
            ],
            $this->buildMenuLocaleFiles($config, $viewData),
        );
    }

    /**
     * @param  array<string, mixed>  $viewData
     * @return list<array{path: string, content: string}>
     */
    protected function buildMenuLocaleFiles(GeneratorTable $config, array $viewData): array
    {
        if (! ($config->options['sync_menu'] ?? true)) {
            return [];
        }

        $menuName = Str::studly($config->api_prefix).'Mgmt';
        $frontendRoot = $this->modulePathResolver->frontendRoot();
        $entriesZh = [$menuName => $config->business_name];
        $entriesEn = [
            $menuName => (string) ($viewData['entity_title_en'] ?? $config->class_name),
        ];

        return [
            [
                'path' => $frontendRoot.'/locales/langs/zh-CN/system.json',
                'content' => $this->localeJsonMerger->mergeMenuKeys(
                    $frontendRoot.'/locales/langs/zh-CN/system.json',
                    $entriesZh,
                ),
            ],
            [
                'path' => $frontendRoot.'/locales/langs/en-US/system.json',
                'content' => $this->localeJsonMerger->mergeMenuKeys(
                    $frontendRoot.'/locales/langs/en-US/system.json',
                    $entriesEn,
                ),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $viewData
     * @return array{zh-CN: array<string, mixed>, en-US: array<string, mixed>}
     */
    protected function buildLocaleEntityEntries(GeneratorTable $config, array $viewData): array
    {
        $columns = $config->columns;
        $columnLabelsZh = [];
        $columnLabelsEn = [];

        foreach ($columns as $column) {
            if (! $column->is_list && ! $column->is_form && ! $column->is_search) {
                continue;
            }

            $columnLabelsZh[$column->column_name] = $this->columnLabel($column);
            $columnLabelsEn[$column->column_name] = ColumnLabelFormatter::fromFieldName($column->column_name);
        }

        $businessName = $config->business_name;
        $businessNameEn = (string) ($viewData['entity_title_en'] ?? $config->class_name);

        return [
            'zh-CN' => [
                'title' => $businessName,
                'searchPlaceholder' => '搜索'.$businessName,
                'columns' => $columnLabelsZh,
                'form' => [
                    'createTitle' => '新增'.$businessName,
                    'editTitle' => '编辑'.$businessName,
                    'viewTitle' => '查看'.$businessName,
                ],
            ],
            'en-US' => [
                'title' => $businessNameEn,
                'searchPlaceholder' => 'Search '.$businessNameEn,
                'columns' => $columnLabelsEn,
                'form' => [
                    'createTitle' => 'Create '.$businessNameEn,
                    'editTitle' => 'Edit '.$businessNameEn,
                    'viewTitle' => 'View '.$businessNameEn,
                ],
            ],
        ];
    }

    protected function normalizeGeneratedRouteContent(string $content): string
    {
        $content = str_replace("\r\n", "\n", $content);
        $content = (string) preg_replace("/\n{3,}/", "\n\n", rtrim($content));

        return $content."\n";
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

    protected function buildGridFormSchemaBody($searchColumns, string $i18nPrefix): string
    {
        $lines = [];
        foreach ($searchColumns as $column) {
            $component = $column->form_component === 'Switch' ? 'Select' : $column->form_component;
            $columnKey = $column->column_name;
            $lines[] = '    {';
            $lines[] = "      component: '{$component}',";
            $lines[] = "      componentProps: { class: 'w-full', placeholder: \$t('{$i18nPrefix}.columns.{$columnKey}') },";
            $lines[] = "      fieldName: '{$columnKey}',";
            $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
            $lines[] = '    },';
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildListColumnsBody($listColumns, string $modelClass, string $permissionsExportName, string $i18nPrefix): string
    {
        $lines = [];
        foreach ($listColumns as $column) {
            $columnKey = $column->column_name;
            if ($column->column_name === 'status') {
                $lines[] = "    {";
                $lines[] = "      field: 'status',";
                $lines[] = "      sortable: true,";
                $lines[] = "      title: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
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
                $lines[] = "      title: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
                $lines[] = '    },';

                continue;
            }

            $lines[] = '    {';
            $lines[] = "      align: 'left',";
            $lines[] = "      field: '{$columnKey}',";
            $lines[] = '      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,';
            $lines[] = '      minWidth: 120,';
            $lines[] = '      sortable: true,';
            $lines[] = "      title: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
            $lines[] = '    },';
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildFormSchemaBody($formColumns, string $i18nPrefix): string
    {
        $lines = [];
        foreach ($formColumns as $column) {
            $component = $column->form_component === 'Switch' ? 'RadioGroup' : $column->form_component;
            $columnKey = $column->column_name;
            $lines[] = '    {';
            $lines[] = "      component: '{$component}',";
            if ($component === 'RadioGroup') {
                $lines[] = '      componentProps: {';
                $lines[] = "        buttonStyle: 'solid',";
                $lines[] = "        class: 'w-full',";
                $lines[] = '        disabled,';
                $lines[] = "        optionType: 'button',";
                $lines[] = '        options: [';
                $lines[] = "          { label: \$t('common.enabled'), value: 1 },";
                $lines[] = "          { label: \$t('common.disabled'), value: 0 },";
                $lines[] = '        ],';
                $lines[] = '      },';
                $lines[] = '      defaultValue: 1,';
            } elseif ($component === 'InputNumber') {
                $lines[] = "      componentProps: { class: 'w-full', disabled, min: 0 },";
                $lines[] = '      defaultValue: 0,';
            } elseif ($component === 'Textarea') {
                $lines[] = "      componentProps: { class: 'w-full', disabled, rows: 2 },";
                $lines[] = "      formItemClass: 'col-span-full w-full items-start',";
            } else {
                $lines[] = "      componentProps: { class: 'w-full', disabled },";
            }
            $lines[] = "      fieldName: '{$columnKey}',";
            $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
            $required = in_array('required', $column->validation_rules ?? [], true);
            if ($required) {
                $lines[] = "      rules: readonly ? undefined : 'required',";
            }
            $lines[] = '    },';
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildApiRowFieldsBody($listColumns): string
    {
        $lines = [];
        foreach ($listColumns as $column) {
            $type = $this->mapTsType($column->ts_type);
            $lines[] = "  {$column->column_name}: {$type};";
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildApiPageQueryFieldsBody($searchColumns): string
    {
        $lines = [];
        foreach ($searchColumns as $column) {
            $type = $column->ts_type === 'number' ? 'number' : 'string';
            $lines[] = "  {$column->column_name}?: {$type};";
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildApiSavePayloadFieldsBody($formColumns): string
    {
        $lines = [];
        foreach ($formColumns as $column) {
            $optional = in_array('required', $column->validation_rules ?? [], true) ? '' : '?';
            $type = $column->ts_type === 'number' ? 'number' : 'string';
            $lines[] = "  {$column->column_name}{$optional}: {$type};";
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildIndexQueryFieldsBody($searchColumns): string
    {
        $lines = [];
        foreach ($searchColumns as $column) {
            $lines[] = "            {$column->column_name}: formValues?.{$column->column_name} || undefined,";
        }

        return implode(PHP_EOL, $lines);
    }

    protected function mapTsType(string $tsType): string
    {
        return match ($tsType) {
            'number' => 'number',
            'Record<string, unknown>' => 'Record<string, unknown>',
            default => 'string',
        };
    }

    protected function columnLabel(object $column): string
    {
        return ColumnLabelFormatter::fromComment(
            $column->column_comment ?? null,
            $column->column_name ?? null,
        );
    }
}
