<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Generator\App\Models\GeneratorRecord;
use Modules\Generator\App\Models\GeneratorTable;
use Modules\Generator\App\Support\ColumnLabelFormatter;
use Modules\Generator\App\Support\FormSchemaBuilder;
use Modules\Generator\App\Support\GeneratorNaming;
use Modules\Generator\App\Support\StatusFieldProfile;
use Modules\Generator\App\Support\StatusFieldResolver;
use Modules\Generator\App\Support\ModulePathResolver;

class CodegenService
{
    public function __construct(
        protected TemplateRenderer $renderer,
        protected FileWriter $fileWriter,
        protected MenuPermissionSync $menuPermissionSync,
        protected ModuleJsonMenuPathSync $moduleJsonMenuPathSync,
        protected GeneratorAuditLogger $auditLogger,
        protected ModulePathResolver $modulePathResolver,
        protected TableConfigService $tableConfigService,
        protected SchemaReader $schemaReader,
        protected LocaleJsonMerger $localeJsonMerger,
        protected ModuleLangEnsurer $moduleLangEnsurer,
        protected FormSchemaBuilder $formSchemaBuilder,
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
            $result['module_json_menu_path'] = $this->moduleJsonMenuPathSync->add(
                $config->module_slug,
                $config->route_path,
            );
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
     * CLI 未传 --module 时：优先用 generator_tables 记录，否则按表名推断。
     */
    public function resolveCliModuleOption(string $tableName, ?string $requestedModule = null): string
    {
        if ($requestedModule !== null && trim($requestedModule) !== '') {
            return $this->modulePathResolver->canonicalModuleSlug($requestedModule);
        }

        $config = $this->findGeneratorConfig($this->schemaReader->resolveTableName($tableName));
        if ($config !== null) {
            return $this->modulePathResolver->canonicalModuleSlug($config->module_slug);
        }

        return $this->modulePathResolver->canonicalModuleSlug(
            GeneratorNaming::fromTableName($tableName)['module_slug'],
        );
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
        $viewData = $this->buildViewData($config);
        $files = $this->buildFiles($config, $viewData);
        $paths = array_values(array_unique(array_merge(
            array_map(static fn (array $file): string => $file['path'], $files),
            $this->localeEntityPaths($config, $viewData),
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
        $moduleJsonMenuPath = $syncMenu
            ? $this->moduleJsonMenuPathSync->remove($config->module_slug, $config->route_path)
            : 'skipped';

        if ($syncMenu) {
            $this->removeMenuLocaleKeys($config, $viewData);
        }

        if ($dropTable) {
            $this->dropPhysicalTable($config);
        }

        if ($persisted) {
            $this->deleteGeneratorConfig($config);
        }

        return array_merge($fileResult, [
            'config' => $config,
            'menu' => $menu,
            'module_json_menu_path' => $moduleJsonMenuPath,
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
            $this->assertRequestedModuleMatches($config, $options, '删除');

            return [$config, true];
        }

        return [$this->buildEphemeralConfig($tableName, $options), false];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function assertRequestedModuleMatches(
        GeneratorTable $config,
        array $options,
        string $action,
    ): void {
        $requestedModule = $this->normalizeModuleOption($options['module'] ?? null);
        if ($requestedModule === null) {
            return;
        }

        if (strtolower($config->module_slug) !== $requestedModule) {
            $canonical = $this->modulePathResolver->canonicalModuleSlug($config->module_slug);
            throw new \InvalidArgumentException(sprintf(
                '表 %s 的生成记录属于模块 %s，与 --module=%s 不一致。请使用 --module=%s，或省略 --module 按记录%s。',
                $config->table_name,
                $canonical,
                $options['module'],
                $canonical,
                $action,
            ));
        }
    }

    protected function normalizeModuleOption(mixed $module): ?string
    {
        if ($module === null || $module === '') {
            return null;
        }

        return strtolower((string) $module);
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

        $moduleSlug = $this->modulePathResolver->canonicalModuleSlug(
            (string) ($options['module'] ?? $naming['module_slug']),
        );
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
        $i18nPrefix = $entityKebab;
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
        $statusProfile = $hasStatus ? $this->resolveStatusProfile($config, $columns) : null;
        if ($statusProfile !== null) {
            $statusColumn = $columns->first(fn ($column) => $column->column_name === 'status');
            if ($statusColumn !== null) {
                $statusColumn->ts_type = $statusProfile->tsType();
            }
        }
        $filteredSearchColumns = $searchColumns
            ->filter(fn ($column): bool => ! in_array($column->column_name, $keywordFields, true))
            ->values();

        $apiRoutePrefix = GeneratorNaming::defaultApiRoutePrefix($config->module_slug);
        $pictureUploadFieldNames = $this->formSchemaBuilder->pictureUploadFieldNames($formColumns);
        $singlePictureUploadFieldNames = $this->formSchemaBuilder->singlePictureUploadFieldNames($formColumns);
        $multiPictureUploadFieldNames = $this->formSchemaBuilder->multiPictureUploadFieldNames($formColumns);
        $attachmentFieldNames = $this->formSchemaBuilder->attachmentFieldNames($formColumns);
        $multiAttachmentFieldNames = $this->formSchemaBuilder->multiAttachmentFieldNames($formColumns);
        $multiValueFieldNames = $this->formSchemaBuilder->multiValueFieldNames($formColumns);
        $datetimeFormFieldNames = $formColumns
            ->filter(function ($column): bool {
                if ($column->form_component !== 'DatePicker') {
                    return false;
                }
                $type = (string) ($column->column_type ?? '');

                return str_contains($type, 'datetime') || str_contains($type, 'timestamp');
            })
            ->pluck('column_name')
            ->values()
            ->all();
        $hasPictureUploadFields = $pictureUploadFieldNames !== [];
        $hasAttachmentFields = $attachmentFieldNames !== [];
        $hasMultiAttachmentFields = $multiAttachmentFieldNames !== [];
        $hasUploadFields = $hasPictureUploadFields || $hasAttachmentFields;
        $hasMultiValueFields = $multiValueFieldNames !== [];
        $hasDatetimeFormFields = $datetimeFormFieldNames !== [];
        $hasDictFields = $formColumns->contains(function ($column): bool {
            $options = is_array($column->form_options ?? null) ? $column->form_options : [];

            return ($options['kind'] ?? null) === 'dict' || ! empty($column->dict_code);
        });
        $hasRelationFields = $formColumns->contains(function ($column): bool {
            $options = is_array($column->form_options ?? null) ? $column->form_options : [];

            return ($options['kind'] ?? null) === 'relation';
        });
        $hasRichEditor = $formColumns->contains(fn ($column): bool => $column->form_component === 'RichEditor');
        $hasListCellHelpers = $listColumns->contains(function ($column): bool {
            $formOptions = is_array($column->form_options ?? null) ? $column->form_options : [];
            if ($column->form_component === 'PictureUploadField' || ($formOptions['kind'] ?? null) === 'image') {
                return ! array_key_exists('max_count', $formOptions);
            }

            return in_array($column->form_component, ['AttachmentField', 'RichEditor', 'IconPicker'], true)
                || in_array($formOptions['kind'] ?? null, ['attachment', 'file'], true);
        });
        $relationImports = $this->formSchemaBuilder->buildRelationImportsBody(
            $formColumns,
            strtolower($config->module_slug),
        );
        $relationLoaders = $this->formSchemaBuilder->buildRelationLoadersBody($formColumns);

        return [
            'config' => $config,
            'api_route_prefix' => $apiRoutePrefix,
            'module_namespace' => $moduleNamespace,
            'module_slug' => $translationNs,
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
            'form_rules_body' => $this->buildFormRulesBody($formColumns, $statusProfile),
            'allowed_sorts_body' => $this->buildAllowedSortsBody($listColumns),
            'search_filters_body' => $this->buildSearchFiltersBody($filteredSearchColumns),
            'grid_form_schema_body' => $this->formSchemaBuilder->buildGridFormSchemaBody($searchColumns, $i18nPrefix, $statusProfile),
            'form_schema_body' => $this->formSchemaBuilder->buildFormSchemaBody($formColumns, $i18nPrefix, $statusProfile),
            'relation_imports_body' => $relationImports === [] ? '' : implode(PHP_EOL, $relationImports),
            'relation_loaders_body' => $relationLoaders === [] ? '' : implode(PHP_EOL, $relationLoaders),
            'has_upload_fields' => $hasUploadFields,
            'has_picture_upload_fields' => $hasPictureUploadFields,
            'has_attachment_fields' => $hasAttachmentFields,
            'has_multi_attachment_fields' => $hasMultiAttachmentFields,
            'multi_attachment_field_names_ts' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", $multiAttachmentFieldNames)),
            'has_multi_value_fields' => $hasMultiValueFields,
            'has_dict_fields' => $hasDictFields,
            'has_relation_fields' => $hasRelationFields,
            'has_rich_editor' => $hasRichEditor,
            'has_list_cell_helpers' => $hasListCellHelpers,
            'picture_upload_field_names_ts' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", $pictureUploadFieldNames)),
            'single_picture_upload_field_names_ts' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", $singlePictureUploadFieldNames)),
            'multi_picture_upload_field_names_ts' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", $multiPictureUploadFieldNames)),
            'has_multi_picture_upload_fields' => $multiPictureUploadFieldNames !== [],
            'multi_value_field_names_ts' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", $multiValueFieldNames)),
            'has_datetime_form_fields' => $hasDatetimeFormFields,
            'datetime_form_field_names_ts' => implode(', ', array_map(static fn (string $field): string => "'{$field}'", $datetimeFormFieldNames)),
            'api_row_fields_body' => $this->buildApiRowFieldsBody($listColumns),
            'api_page_query_fields_body' => $this->buildApiPageQueryFieldsBody($searchColumns),
            'api_save_payload_fields_body' => $this->buildApiSavePayloadFieldsBody($formColumns),
            'index_query_fields_body' => $this->buildIndexQueryFieldsBody($searchColumns),
            'list_columns_body' => $this->buildListColumnsBody($listColumns, $config->class_name, strtoupper(str_replace(['.', '-'], '_', $config->permission_prefix)).'_PERMISSIONS', $i18nPrefix, $statusProfile),
            'primary_label_field' => $listColumns->first()?->column_name ?? 'id',
            'has_status' => $hasStatus,
            'status_is_integer' => $statusProfile?->isInteger() ?? true,
            'status_ts_union' => $statusProfile?->tsUnionType() ?? '0 | 1',
            'status_enabled_js' => $statusProfile?->jsLiteral($statusProfile->enabledValue) ?? '1',
            'status_disabled_js' => $statusProfile?->jsLiteral($statusProfile->disabledValue) ?? '0',
            'status_enabled_label_ts' => $statusProfile?->labelTsExpr($statusProfile->enabledValue) ?? "\$t('common.enabled')",
            'status_disabled_label_ts' => $statusProfile?->labelTsExpr($statusProfile->disabledValue) ?? "\$t('common.disabled')",
            'status_batch_validation_body' => $this->buildStatusBatchValidationBody($statusProfile),
            'status_form_default_js' => $statusProfile?->jsLiteral($statusProfile->enabledValue) ?? '1',
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
        $featureRoot = $this->modulePathResolver->frontendFeatureRoot($config->module_slug);

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
                'path' => $featureRoot.'/api/'.$viewData['entity_kebab'].'.ts',
                'content' => $this->renderer->render('frontend/api.stub', $viewData),
            ],
            [
                'path' => $featureRoot.'/constants/'.$viewData['entity_kebab'].'-permissions.ts',
                'content' => $this->renderer->render('frontend/permissions.ts.stub', $viewData),
            ],
            [
                'path' => $featureRoot.'/views/'.$config->route_group.'/'.$viewData['entity_kebab'].'/index.vue',
                'content' => $this->renderer->render('frontend/index.vue.stub', $viewData),
            ],
            [
                'path' => $featureRoot.'/views/'.$config->route_group.'/'.$viewData['entity_kebab'].'/data.ts',
                'content' => $this->renderer->render('frontend/data.ts.stub', $viewData),
            ],
            [
                'path' => $featureRoot.'/views/'.$config->route_group.'/'.$viewData['entity_kebab'].'/modules/form.vue',
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
        $featureRoot = $this->modulePathResolver->frontendFeatureRoot($config->module_slug);
        $moduleSlug = strtolower($config->module_slug);
        $localeBasename = $this->localeEntityBasename($config, $viewData);
        $entries = $this->buildLocaleEntityEntries($config, $viewData);

        $files = [
            [
                'path' => $featureRoot.'/locales/zh-CN/'.$localeBasename.'.json',
                'content' => $this->localeJsonMerger->encodeEntityFile($entries['zh-CN']),
            ],
            [
                'path' => $featureRoot.'/locales/en-US/'.$localeBasename.'.json',
                'content' => $this->localeJsonMerger->encodeEntityFile($entries['en-US']),
            ],
        ];

        if ($config->options['sync_menu'] ?? true) {
            $menuName = Str::studly($config->api_prefix).'Mgmt';
            $files[] = [
                'path' => $featureRoot.'/locales/zh-CN/'.$moduleSlug.'.json',
                'content' => $this->localeJsonMerger->mergeMenuKeys(
                    $featureRoot.'/locales/zh-CN/'.$moduleSlug.'.json',
                    [$menuName => $config->business_name],
                ),
            ];
            $files[] = [
                'path' => $featureRoot.'/locales/en-US/'.$moduleSlug.'.json',
                'content' => $this->localeJsonMerger->mergeMenuKeys(
                    $featureRoot.'/locales/en-US/'.$moduleSlug.'.json',
                    [
                        $menuName => (string) ($viewData['entity_title_en'] ?? $config->class_name),
                    ],
                ),
            ];
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    protected function localeEntityPaths(GeneratorTable $config, array $viewData): array
    {
        $featureRoot = $this->modulePathResolver->frontendFeatureRoot($config->module_slug);
        $localeBasename = $this->localeEntityBasename($config, $viewData);

        return [
            $featureRoot.'/locales/zh-CN/'.$localeBasename.'.json',
            $featureRoot.'/locales/en-US/'.$localeBasename.'.json',
        ];
    }

    protected function localeEntityBasename(GeneratorTable $config, array $viewData): string
    {
        return (string) $viewData['entity_kebab'];
    }

    protected function removeMenuLocaleKeys(GeneratorTable $config, array $viewData): void
    {
        if (! ($config->options['sync_menu'] ?? true)) {
            return;
        }

        $featureRoot = $this->modulePathResolver->frontendFeatureRoot($config->module_slug);
        $moduleSlug = strtolower($config->module_slug);
        $menuName = Str::studly($config->api_prefix).'Mgmt';

        $this->localeJsonMerger->removeMenuKeys(
            $featureRoot.'/locales/zh-CN/'.$moduleSlug.'.json',
            [$menuName],
        );
        $this->localeJsonMerger->removeMenuKeys(
            $featureRoot.'/locales/en-US/'.$moduleSlug.'.json',
            [$menuName],
        );
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
                'batchDeleteConfirm' => '确定删除选中的 {0} 条'.$businessName.'吗？',
                'batchStatusConfirm' => '确定将选中的 {0} 条'.$businessName.'批量【{1}】吗？',
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
                'batchDeleteConfirm' => 'Delete {0} selected '.$businessNameEn.' record(s)?',
                'batchStatusConfirm' => 'Batch set {0} '.$businessNameEn.' record(s) to [{1}]?',
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

    protected function buildFormRulesBody($formColumns, ?StatusFieldProfile $statusProfile = null): string
    {
        $lines = [];
        foreach ($formColumns as $column) {
            $rules = collect($column->validation_rules ?? []);
            if ($column->column_name === 'status' && $statusProfile !== null) {
                $rules = $rules->reject(static fn (string $rule): bool => str_starts_with($rule, 'in:'));
                $rules->push($statusProfile->laravelInRule());
            }
            $ruleString = $rules
                ->map(static fn (string $rule): string => "'{$rule}'")
                ->implode(', ');
            $lines[] = "            '{$column->column_name}' => [{$ruleString}],";
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
            if ($column->column_name === 'status') {
                if ($column->ts_type === 'number') {
                    $lines[] = "        if (\$request->filled('status')) {";
                    $lines[] = "            \$query->where('status', (int) \$request->get('status'));";
                    $lines[] = '        }';
                } else {
                    $lines[] = "        if (\$request->filled('status')) {";
                    $lines[] = "            \$query->where('status', (string) \$request->get('status'));";
                    $lines[] = '        }';
                }

                continue;
            }

            $formOptions = is_array($column->form_options ?? null) ? $column->form_options : [];
            $isExactSearch = in_array($column->form_component, ['Select', 'ApiSelect', 'ApiTreeSelect', 'Switch', 'RadioGroup'], true)
                || ($formOptions['kind'] ?? null) === 'enum'
                || ($formOptions['kind'] ?? null) === 'relation';

            if ($column->ts_type === 'number' || $isExactSearch) {
                $lines[] = "        if (\$request->filled('{$column->column_name}')) {";
                $lines[] = "            \$query->where('{$column->column_name}', \$request->get('{$column->column_name}'));";
                $lines[] = '        }';
            } else {
                $lines[] = "        if (\$request->filled('{$column->column_name}')) {";
                $lines[] = "            \$query->where('{$column->column_name}', 'like', '%'.\$request->get('{$column->column_name}').'%');";
                $lines[] = '        }';
            }
        }

        return implode(PHP_EOL, $lines);
    }

    protected function buildGridFormSchemaBody($searchColumns, string $i18nPrefix, ?StatusFieldProfile $statusProfile): string
    {
        return $this->formSchemaBuilder->buildGridFormSchemaBody($searchColumns, $i18nPrefix, $statusProfile);
    }

    protected function buildFormSchemaBody($formColumns, string $i18nPrefix, ?StatusFieldProfile $statusProfile): string
    {
        return $this->formSchemaBuilder->buildFormSchemaBody($formColumns, $i18nPrefix, $statusProfile);
    }

    protected function buildListColumnsBody(
        $listColumns,
        string $modelClass,
        string $permissionsExportName,
        string $i18nPrefix,
        ?StatusFieldProfile $statusProfile,
    ): string {
        $lines = [];
        foreach ($listColumns as $column) {
            $columnKey = $column->column_name;
            if ($column->column_name === 'status') {
                $statusCast = $statusProfile?->isInteger() ?? true
                    ? 'number'
                    : "{$modelClass}Row['status']";
                $lines[] = '    {';
                $lines[] = "      field: 'status',";
                $lines[] = '      sortable: true,';
                $lines[] = "      title: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
                $lines[] = '      width: 100,';
                $lines[] = '      cellRender: {';
                $lines[] = '        attrs: {';
                $lines[] = "          async beforeChange(newVal: unknown, row: {$modelClass}Row) {";
                $lines[] = "            return onStatusBeforeChange(newVal as {$statusCast}, row);";
                $lines[] = '          },';
                if ($statusProfile !== null && ! $statusProfile->isInteger()) {
                    $lines[] = '          getSwitchProps: () => ({';
                    $lines[] = "            checkedChildren: {$statusProfile->labelTsExpr($statusProfile->enabledValue)},";
                    $lines[] = "            checkedValue: {$statusProfile->jsLiteral($statusProfile->enabledValue)},";
                    $lines[] = '            disabled: !canUpdate,';
                    $lines[] = "            unCheckedChildren: {$statusProfile->labelTsExpr($statusProfile->disabledValue)},";
                    $lines[] = "            unCheckedValue: {$statusProfile->jsLiteral($statusProfile->disabledValue)},";
                    $lines[] = '          }),';
                } else {
                    $lines[] = '          getSwitchProps: () => ({ disabled: !canUpdate }),';
                }
                $lines[] = '        },';
                $lines[] = "        name: 'CellSwitch',";
                $lines[] = '      },';
                $lines[] = '    },';

                continue;
            }

            $formOptions = is_array($column->form_options ?? null) ? $column->form_options : [];
            if ($column->form_component === 'PictureUploadField' || ($formOptions['kind'] ?? null) === 'image') {
                $isMultiImage = ! array_key_exists('max_count', $formOptions);
                $lines[] = '    {';
                $lines[] = "      align: 'center',";
                $lines[] = "      field: '{$columnKey}',";
                $lines[] = '      cellRender: {';
                if ($isMultiImage) {
                    $lines[] = '        attrs: {';
                    $lines[] = "          getExtraCount: (row: {$modelClass}Row) => csvExtraCount(row.{$columnKey}),";
                    $lines[] = "          getSrc: (row: {$modelClass}Row) => firstCsvValue(row.{$columnKey}),";
                    $lines[] = '        },';
                }
                $lines[] = "        name: 'CellImage',";
                $lines[] = '        props: { height: 40, preview: true, width: 40 },';
                $lines[] = '      },';
                $lines[] = "      title: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
                $lines[] = '      width: '.($isMultiImage ? 100 : 80).',';
                $lines[] = '    },';

                continue;
            }

            if ($column->form_component === 'AttachmentField'
                || in_array($formOptions['kind'] ?? null, ['attachment', 'file'], true)) {
                $lines[] = '    {';
                $lines[] = "      align: 'left',";
                $lines[] = "      field: '{$columnKey}',";
                $lines[] = '      cellRender: {';
                $lines[] = '        attrs: {';
                $lines[] = "          getExtraCount: (row: {$modelClass}Row) => csvExtraCount(row.{$columnKey}),";
                $lines[] = "          getFileName: (row: {$modelClass}Row) => extractFileNameFromUrl(firstCsvValue(row.{$columnKey} ?? '')),";
                $lines[] = "          getUrl: (row: {$modelClass}Row) => firstCsvValue(row.{$columnKey}),";
                $lines[] = '        },';
                $lines[] = "        name: 'CellFile',";
                $lines[] = '        props: { maxFileNameLength: 24, preview: true },';
                $lines[] = '      },';
                $lines[] = '      minWidth: 140,';
                $lines[] = "      title: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
                $lines[] = '    },';

                continue;
            }

            if ($column->form_component === 'RichEditor') {
                $lines[] = '    {';
                $lines[] = "      align: 'left',";
                $lines[] = "      field: '{$columnKey}',";
                $lines[] = '      formatter: ({ cellValue }) =>';
                $lines[] = '        stripHtmlSummary(cellValue, 48) || TABLE_CELL_EMPTY,';
                $lines[] = '      minWidth: 160,';
                $lines[] = "      title: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
                $lines[] = '    },';

                continue;
            }

            if ($column->form_component === 'IconPicker') {
                $lines[] = '    {';
                $lines[] = "      align: 'center',";
                $lines[] = "      field: '{$columnKey}',";
                $lines[] = '      cellRender: {';
                $lines[] = '        attrs: {';
                $lines[] = "          getIcon: (row: {$modelClass}Row) => row.{$columnKey},";
                $lines[] = '        },';
                $lines[] = "        name: 'CellIcon',";
                $lines[] = '      },';
                $lines[] = "      title: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
                $lines[] = '      width: 72,';
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

    protected function resolveStatusProfile(GeneratorTable $config, $columns): ?StatusFieldProfile
    {
        $statusColumn = $columns->first(fn ($column) => $column->column_name === 'status');
        if ($statusColumn === null) {
            return null;
        }

        $schemaComment = null;
        foreach ($this->schemaReader->listColumns($config->table_name) as $schemaColumn) {
            if ($schemaColumn['name'] === 'status') {
                $schemaComment = $schemaColumn['comment'] ?? null;
                break;
            }
        }

        return StatusFieldResolver::fromColumnTypeAndComment(
            (string) $statusColumn->column_type,
            $schemaComment ?? (string) ($statusColumn->column_comment ?? null),
            is_array($statusColumn->validation_rules) ? $statusColumn->validation_rules : null,
        );
    }

    protected function buildStatusBatchValidationBody(?StatusFieldProfile $profile): string
    {
        if ($profile === null) {
            return "'status' => ['required', 'integer', 'in:0,1'],";
        }

        return sprintf(
            "'status' => ['required', '%s', '%s'],",
            $profile->laravelValidationType(),
            $profile->laravelInRule(),
        );
    }

    protected function buildStatusOptionLines(StatusFieldProfile $profile, string $indent): string
    {
        $lines = [];
        foreach ($profile->allowedValues as $value) {
            $lines[] = $indent."  { label: {$profile->labelTsExpr($value)}, value: {$profile->jsLiteral($value)} },";
        }

        return implode(PHP_EOL, $lines);
    }
}
