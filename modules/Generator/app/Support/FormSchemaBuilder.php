<?php

namespace Modules\Generator\App\Support;

use Illuminate\Support\Str;

final class FormSchemaBuilder
{
    public function buildFormSchemaBody($formColumns, string $i18nPrefix, ?StatusFieldProfile $statusProfile): string
    {
        $lines = [];
        foreach ($formColumns as $column) {
            $lines = array_merge($lines, $this->buildFormFieldLines($column, $i18nPrefix, $statusProfile));
        }

        return implode(PHP_EOL, $lines);
    }

    public function buildGridFormSchemaBody($searchColumns, string $i18nPrefix, ?StatusFieldProfile $statusProfile): string
    {
        $lines = [];
        foreach ($searchColumns as $column) {
            $columnKey = $column->column_name;
            if ($column->column_name === 'status' && $statusProfile !== null) {
                $lines = array_merge($lines, $this->buildStatusSelectLines($columnKey, $i18nPrefix, $statusProfile));

                continue;
            }

            $options = is_array($column->form_options ?? null) ? $column->form_options : [];
            if (($options['kind'] ?? null) === 'enum' && ! empty($options['options'])) {
                $lines = array_merge($lines, $this->buildEnumSelectLines($column, $i18nPrefix, true));

                continue;
            }

            if (in_array($column->form_component, ['ApiSelect', 'ApiTreeSelect'], true)) {
                $loaderFn = (string) ($options['loader_fn'] ?? '');
                $lines[] = '    {';
                $lines[] = "      component: 'Select',";
                $lines[] = '      componentProps: {';
                $lines[] = "        allowClear: true,";
                $lines[] = "        class: 'w-full',";
                $lines[] = '        options: [],';
                $lines[] = "        placeholder: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
                $lines[] = '      },';
                $lines[] = "      fieldName: '{$columnKey}',";
                $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
                $lines[] = '    },';

                continue;
            }

            $component = $column->form_component === 'Switch' ? 'Select' : $column->form_component;
            $lines[] = '    {';
            $lines[] = "      component: '{$component}',";
            $lines[] = "      componentProps: { class: 'w-full', placeholder: \$t('{$i18nPrefix}.columns.{$columnKey}') },";
            $lines[] = "      fieldName: '{$columnKey}',";
            $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
            $lines[] = '    },';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return list<string>
     */
    public function buildRelationLoadersBody($formColumns): array
    {
        $lines = [];
        $seen = [];
        foreach ($formColumns as $column) {
            $options = is_array($column->form_options ?? null) ? $column->form_options : [];
            if (($options['kind'] ?? null) !== 'relation') {
                continue;
            }

            $loaderFn = (string) ($options['loader_fn'] ?? '');
            if ($loaderFn === '' || isset($seen[$loaderFn])) {
                continue;
            }
            $seen[$loaderFn] = true;

            $apiFn = (string) ($options['api_fn'] ?? '');
            $labelField = (string) ($options['label_field'] ?? 'name');
            $mode = (string) ($options['mode'] ?? 'select');
            $mapperFn = (string) ($options['mapper_fn'] ?? '');

            $lines[] = "async function {$loaderFn}() {";
            if ($mapperFn !== '') {
                $lines[] = "  const data = await {$apiFn}();";
                $lines[] = "  return {$mapperFn}(data);";
            } elseif ($mode === 'tree') {
                $lines[] = "  const page = await {$apiFn}({ page: 1, per_page: 500 });";
                $lines[] = '  return page.data.map((row) => ({';
                $lines[] = "    label: String(row.{$labelField} ?? row.id),";
                $lines[] = '    value: row.id,';
                $lines[] = '    children: [],';
                $lines[] = '  }));';
            } else {
                $lines[] = "  const page = await {$apiFn}({ page: 1, per_page: 500 });";
                $lines[] = '  return page.data.map((row) => ({';
                $lines[] = "    label: String(row.{$labelField} ?? row.id),";
                $lines[] = '    value: row.id,';
                $lines[] = '  }));';
            }
            $lines[] = '}';
            $lines[] = '';
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    public function buildRelationImportsBody($formColumns): array
    {
        $imports = [];
        foreach ($formColumns as $column) {
            $options = is_array($column->form_options ?? null) ? $column->form_options : [];
            if (($options['kind'] ?? null) !== 'relation') {
                continue;
            }

            $apiFn = (string) ($options['api_fn'] ?? '');
            $apiRoutePrefix = (string) ($options['api_route_prefix'] ?? 'system');
            $apiEntity = (string) ($options['api_entity'] ?? '');
            if ($apiFn === '' || $apiEntity === '') {
                continue;
            }

            $imports["#/api/{$apiRoutePrefix}/{$apiEntity}"][] = $apiFn;
            foreach ($options['extra_imports'] ?? [] as $extraImport) {
                $imports["#/api/{$apiRoutePrefix}/{$apiEntity}"][] = (string) $extraImport;
            }
        }

        $lines = [];
        foreach ($imports as $path => $functions) {
            $functions = array_values(array_unique($functions));
            $lines[] = 'import { '.implode(', ', $functions)." } from '{$path}';";
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function buildEnumRadioLines(object $column, string $i18nPrefix): array
    {
        $columnKey = $column->column_name;
        $options = is_array($column->form_options ?? null) ? $column->form_options : [];
        $enumOptions = $options['options'] ?? [];

        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'RadioGroup',";
        $lines[] = '      componentProps: {';
        $lines[] = "        buttonStyle: 'solid',";
        $lines[] = "        class: 'w-full',";
        $lines[] = '        disabled,';
        $lines[] = "        optionType: 'button',";
        $lines[] = '        options: [';
        foreach ($enumOptions as $option) {
            $label = addslashes((string) ($option['label'] ?? $option['value'] ?? ''));
            $value = $option['value'] ?? '';
            $valueJs = is_numeric($value) ? (string) (int) $value : "'".addslashes((string) $value)."'";
            $lines[] = "          { label: '{$label}', value: {$valueJs} },";
        }
        $lines[] = '        ],';
        $lines[] = '      },';
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      formItemClass: 'items-start',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        if (in_array('required', $column->validation_rules ?? [], true)) {
            $lines[] = "      rules: readonly ? undefined : 'required',";
        }
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function buildEnumCheckboxGroupLines(object $column, string $i18nPrefix): array
    {
        $columnKey = $column->column_name;
        $options = is_array($column->form_options ?? null) ? $column->form_options : [];
        $enumOptions = $options['options'] ?? [];

        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'CheckboxGroup',";
        $lines[] = '      componentProps: {';
        $lines[] = "        class: 'w-full',";
        $lines[] = '        disabled,';
        $lines[] = '        options: [';
        foreach ($enumOptions as $option) {
            $label = addslashes((string) ($option['label'] ?? $option['value'] ?? ''));
            $value = addslashes((string) ($option['value'] ?? ''));
            $lines[] = "          { label: '{$label}', value: '{$value}' },";
        }
        $lines[] = '        ],';
        $lines[] = '      },';
        $lines[] = "      fieldName: '{$columnKey}',";
        if (count($enumOptions) > 4) {
            $lines[] = "      formItemClass: 'col-span-full w-full items-start',";
        } else {
            $lines[] = "      formItemClass: 'items-start',";
        }
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function buildBooleanSwitchLines(object $column, string $i18nPrefix): array
    {
        $columnKey = $column->column_name;
        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'Switch',";
        $lines[] = "      componentProps: { class: 'w-auto', disabled },";
        $lines[] = '      defaultValue: false,';
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function buildComponentFieldLines(object $column, string $i18nPrefix): array
    {
        $component = $column->form_component === 'Switch' ? 'RadioGroup' : $column->form_component;
        $columnKey = $column->column_name;
        $options = is_array($column->form_options ?? null) ? $column->form_options : [];
        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: '{$component}',";

        switch ($component) {
            case 'RadioGroup':
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
                break;
            case 'InputNumber':
                $lines[] = "      componentProps: { class: 'w-full', disabled, min: 0 },";
                $lines[] = '      defaultValue: 0,';
                break;
            case 'InputPassword':
                $lines[] = "      componentProps: { class: 'w-full', disabled, autocomplete: 'new-password' },";
                break;
            case 'Textarea':
                $lines[] = "      componentProps: { class: 'w-full', disabled, rows: 4 },";
                $lines[] = "      formItemClass: 'col-span-full w-full items-start',";
                break;
            case 'DatePicker':
                $showTime = str_contains($column->column_type, 'datetime') || str_contains($column->column_type, 'timestamp');
                $lines[] = '      componentProps: {';
                $lines[] = "        class: 'w-full',";
                $lines[] = '        disabled,';
                $lines[] = '        showTime: '.($showTime ? 'true' : 'false').',';
                $lines[] = "        valueFormat: '".($showTime ? 'YYYY-MM-DD HH:mm:ss' : 'YYYY-MM-DD')."',";
                $lines[] = '      },';
                break;
            case 'TimePicker':
                $lines[] = '      componentProps: {';
                $lines[] = "        class: 'w-full',";
                $lines[] = '        disabled,';
                $lines[] = "        valueFormat: '".($options['value_format'] ?? 'HH:mm:ss')."',";
                $lines[] = '      },';
                break;
            case 'Rate':
                $lines[] = '      componentProps: {';
                $lines[] = '        disabled,';
                $lines[] = '        count: '.(int) ($options['count'] ?? 5).',';
                $lines[] = '      },';
                $lines[] = '      defaultValue: 0,';
                break;
            case 'IconPicker':
                $lines[] = "      componentProps: { class: 'w-full', disabled },";
                break;
            case 'AutoComplete':
                $autoOptions = $options['options'] ?? [];
                $lines[] = '      componentProps: {';
                $lines[] = "        class: 'w-full',";
                $lines[] = '        disabled,';
                $lines[] = '        options: [';
                foreach ($autoOptions as $item) {
                    $label = addslashes((string) $item);
                    $lines[] = "          { label: '{$label}', value: '{$label}' },";
                }
                $lines[] = '        ],';
                $lines[] = '      },';
                break;
            case 'Mentions':
                $mentionOptions = $options['options'] ?? ['@admin'];
                $lines[] = '      componentProps: {';
                $lines[] = "        class: 'w-full',";
                $lines[] = '        disabled,';
                $lines[] = '        options: [';
                foreach ($mentionOptions as $item) {
                    $label = addslashes((string) $item);
                    $lines[] = "          { label: '{$label}', value: '{$label}' },";
                }
                $lines[] = '        ],';
                $lines[] = '      },';
                $lines[] = "      formItemClass: 'col-span-full w-full items-start',";
                break;
            default:
                $lines[] = "      componentProps: { class: 'w-full', disabled },";
                break;
        }

        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        if (in_array('required', $column->validation_rules ?? [], true)) {
            $lines[] = "      rules: readonly ? undefined : 'required',";
        }
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @return list<string>
     */
    public function uploadFieldNames($formColumns): array
    {
        $names = [];
        foreach ($formColumns as $column) {
            $options = is_array($column->form_options ?? null) ? $column->form_options : [];
            if (! in_array($options['kind'] ?? null, ['image', 'attachment', 'file'], true)
                && ! in_array($column->form_component, ['PictureUploadField', 'AttachmentField', 'Upload'], true)) {
                continue;
            }
            $names[] = $column->column_name;
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    protected function buildFormFieldLines(object $column, string $i18nPrefix, ?StatusFieldProfile $statusProfile): array
    {
        $columnKey = $column->column_name;
        $options = is_array($column->form_options ?? null) ? $column->form_options : [];

        if ($column->column_name === 'status' && $statusProfile !== null) {
            return $this->buildStatusRadioLines($columnKey, $i18nPrefix, $statusProfile);
        }

        if (($options['kind'] ?? null) === 'enum' || ($options['kind'] ?? null) === 'dict') {
            return match ($column->form_component) {
                'RadioGroup' => $this->buildEnumRadioLines($column, $i18nPrefix),
                'CheckboxGroup' => $this->buildEnumCheckboxGroupLines($column, $i18nPrefix),
                default => $this->buildEnumSelectLines($column, $i18nPrefix, false),
            };
        }

        if (($options['kind'] ?? null) === 'relation') {
            return $this->buildRelationFieldLines($column, $i18nPrefix, $options);
        }

        if (($options['kind'] ?? null) === 'image' || $column->form_component === 'PictureUploadField') {
            return $this->buildPictureUploadLines($columnKey, $i18nPrefix, $options);
        }

        if (($options['kind'] ?? null) === 'attachment' || $column->form_component === 'AttachmentField') {
            return $this->buildAttachmentFieldLines($columnKey, $i18nPrefix, $options);
        }

        if (($options['kind'] ?? null) === 'file' || $column->form_component === 'Upload') {
            return $this->buildUploadLines($columnKey, $i18nPrefix);
        }

        if ($column->form_component === 'RichEditor') {
            return $this->buildRichEditorLines($columnKey, $i18nPrefix);
        }

        if ($column->form_component === 'Switch' && ($options['kind'] ?? null) === 'boolean') {
            return $this->buildBooleanSwitchLines($column, $i18nPrefix);
        }

        return $this->buildComponentFieldLines($column, $i18nPrefix);
    }

    /**
     * @return list<string>
     */
    public function multiValueFieldNames($formColumns): array
    {
        $names = [];
        foreach ($formColumns as $column) {
            if ($column->form_component === 'CheckboxGroup') {
                $names[] = $column->column_name;
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    protected function buildStatusRadioLines(string $columnKey, string $i18nPrefix, StatusFieldProfile $statusProfile): array
    {
        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'RadioGroup',";
        $lines[] = '      componentProps: {';
        $lines[] = "        buttonStyle: 'solid',";
        $lines[] = "        class: 'w-full',";
        $lines[] = '        disabled,';
        $lines[] = "        optionType: 'button',";
        $lines[] = '        options: [';
        $lines[] = $this->buildStatusOptionLines($statusProfile, '        ');
        $lines[] = '        ],';
        $lines[] = '      },';
        $lines[] = "      defaultValue: {$statusProfile->jsLiteral($statusProfile->enabledValue)},";
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function buildStatusSelectLines(string $columnKey, string $i18nPrefix, StatusFieldProfile $statusProfile): array
    {
        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'Select',";
        $lines[] = '      componentProps: {';
        $lines[] = "        allowClear: true,";
        $lines[] = "        class: 'w-full',";
        $lines[] = "        options: [";
        $lines[] = $this->buildStatusOptionLines($statusProfile, '        ');
        $lines[] = '        ],';
        $lines[] = "        placeholder: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '      },';
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function buildEnumSelectLines(object $column, string $i18nPrefix, bool $search): array
    {
        $columnKey = $column->column_name;
        $options = is_array($column->form_options ?? null) ? $column->form_options : [];
        $enumOptions = $options['options'] ?? [];
        $multiple = (bool) ($options['multiple'] ?? false);
        $dictCode = (string) ($options['dict_code'] ?? $column->dict_code ?? '');

        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'Select',";
        $lines[] = '      componentProps: {';
        if ($search) {
            $lines[] = "        allowClear: true,";
        }
        $lines[] = "        class: 'w-full',";
        if ($multiple) {
            $lines[] = "        mode: 'multiple',";
        }
        if ($dictCode !== '') {
            $camel = Str::camel($dictCode);
            $lines[] = "        options: mapDictItemOptions(DICT_TYPE_CODE.{$camel}, []),";
        } else {
            $lines[] = '        options: [';
            foreach ($enumOptions as $option) {
                $label = addslashes((string) ($option['label'] ?? $option['value'] ?? ''));
                $value = $option['value'] ?? '';
                $valueJs = is_numeric($value) ? (string) (int) $value : "'".addslashes((string) $value)."'";
                $lines[] = "          { label: '{$label}', value: {$valueJs} },";
            }
            $lines[] = '        ],';
        }
        $lines[] = "        placeholder: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        if (! $search) {
            $lines[] = '        disabled,';
        }
        $lines[] = '      },';
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        if (! $search && in_array('required', $column->validation_rules ?? [], true)) {
            $lines[] = "      rules: readonly ? undefined : 'required',";
        }
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<string>
     */
    protected function buildRelationFieldLines(object $column, string $i18nPrefix, array $options): array
    {
        $columnKey = $column->column_name;
        $loaderFn = (string) ($options['loader_fn'] ?? '');
        $component = (string) ($options['component'] ?? 'ApiSelect');
        $mode = (string) ($options['mode'] ?? 'select');

        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: '{$component}',";
        $lines[] = '      componentProps: {';
        $lines[] = "        allowClear: true,";
        $lines[] = "        api: {$loaderFn},";
        $lines[] = "        class: 'w-full',";
        if ($component === 'ApiTreeSelect') {
            $lines[] = "        fieldNames: { children: 'children', label: 'label', value: 'value' },";
            $lines[] = '        treeDefaultExpandAll: true,';
            $lines[] = '        treeIcon: false,';
            $lines[] = '        treeLine: false,';
        } else {
            $lines[] = "        optionFilterProp: 'label',";
            $lines[] = '        showSearch: true,';
        }
        $lines[] = "        placeholder: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '        disabled,';
        $lines[] = '      },';
        if ($columnKey === 'parent_id') {
            $lines[] = '      defaultValue: 0,';
        }
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        if (in_array('required', $column->validation_rules ?? [], true)) {
            $lines[] = "      rules: readonly ? undefined : 'required',";
        }
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<string>
     */
    protected function buildPictureUploadLines(string $columnKey, string $i18nPrefix, array $options): array
    {
        $maxCount = (int) ($options['max_count'] ?? 1);
        $formItemClass = $maxCount > 1
            ? 'picture-upload-form-item col-span-full w-full items-start'
            : 'picture-upload-form-item items-start';
        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'PictureUploadField',";
        $lines[] = '      componentProps: {';
        $lines[] = '        customRequest: params.onImageUpload,';
        $lines[] = '        handleChange: params.onImageChange,';
        $lines[] = "        maxCount: {$maxCount},";
        $lines[] = '        disabled,';
        $lines[] = '      },';
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      formItemClass: '{$formItemClass}',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<string>
     */
    protected function buildAttachmentFieldLines(string $columnKey, string $i18nPrefix, array $options): array
    {
        $maxCount = (int) ($options['max_count'] ?? 1);
        $formItemClass = $maxCount > 1
            ? 'col-span-full items-start'
            : 'items-start';
        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'AttachmentField',";
        $lines[] = '      componentProps: {';
        $lines[] = '        customRequest: params.onAttachmentUpload,';
        $lines[] = "        maxCount: {$maxCount},";
        $lines[] = '        disabled,';
        $lines[] = '      },';
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      formItemClass: '{$formItemClass}',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function buildUploadLines(string $columnKey, string $i18nPrefix): array
    {
        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'Upload',";
        $lines[] = '      componentProps: {';
        $lines[] = '        customRequest: params.onFileUpload,';
        $lines[] = "        maxCount: 1,";
        $lines[] = '        disabled,';
        $lines[] = '      },';
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      formItemClass: 'items-start',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '    },';

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function buildRichEditorLines(string $columnKey, string $i18nPrefix): array
    {
        $lines = [];
        $lines[] = '    {';
        $lines[] = "      component: 'RichEditor',";
        $lines[] = "      componentProps: { class: 'w-full', disabled },";
        $lines[] = "      fieldName: '{$columnKey}',";
        $lines[] = "      formItemClass: 'col-span-full w-full items-start',";
        $lines[] = "      label: \$t('{$i18nPrefix}.columns.{$columnKey}'),";
        $lines[] = '    },';

        return $lines;
    }

    protected function buildStatusOptionLines(StatusFieldProfile $statusProfile, string $indent): string
    {
        $lines = [];
        foreach ($statusProfile->allowedValues as $value) {
            $label = $statusProfile->labelTsExpr($value);
            $valueJs = $statusProfile->jsLiteral($value);
            $lines[] = "{$indent}{ label: {$label}, value: {$valueJs} },";
        }

        return implode(PHP_EOL, $lines);
    }
}
