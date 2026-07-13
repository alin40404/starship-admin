<?php

namespace Modules\Generator\App\Services;

use Modules\Generator\App\Support\ColumnLabelFormatter;
use Modules\Generator\App\Support\FormFieldInferencer;
use Modules\Generator\App\Support\StatusFieldResolver;

class ColumnInferencer
{
    /** @var list<string> */
    protected array $systemColumns = [
        'id',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function __construct(
        protected SchemaReader $schemaReader,
        protected FormFieldInferencer $formFieldInferencer,
    ) {}

    /**
     * @param  list<array{
     *   name: string,
     *   type: string,
     *   nullable: bool,
     *   key: string,
     *   default: mixed,
     *   extra: string,
     *   comment: string|null
     * }>  $columns
     * @return list<array<string, mixed>>
     */
    public function inferMany(array $columns, string $tableName = ''): array
    {
        $foreignKeys = $tableName !== '' ? $this->schemaReader->listForeignKeys($tableName) : [];
        $result = [];
        foreach ($columns as $index => $column) {
            $result[] = $this->infer($column, $index, $tableName, $foreignKeys);
        }

        return $result;
    }

    /**
     * @param  array{
     *   name: string,
     *   type: string,
     *   nullable: bool,
     *   key: string,
     *   default: mixed,
     *   extra: string,
     *   comment: string|null
     * }  $column
     * @param  list<array{column: string, referenced_table: string, referenced_column: string}>  $foreignKeys
     * @return array<string, mixed>
     */
    public function infer(array $column, int $sort = 0, string $tableName = '', array $foreignKeys = []): array
    {
        $name = $column['name'];
        $type = strtolower($column['type']);
        $isPk = $column['key'] === 'PRI';
        $isStatus = $name === 'status';
        $isJson = str_contains($type, 'json');
        $comment = $column['comment'] ?? null;
        $baseRules = $this->inferValidationRules($name, $type, $column['nullable'], $comment);
        $fieldMeta = $this->formFieldInferencer->infer($column, $tableName, $foreignKeys, $baseRules);

        return [
            'column_name' => $name,
            'column_type' => $column['type'],
            'column_comment' => ColumnLabelFormatter::fromComment($comment, $name),
            'ts_type' => $this->inferTsType($type, $isStatus, $comment, $fieldMeta['form_component']),
            'is_pk' => $isPk,
            'is_list' => ! $isPk && ! in_array($name, ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'], true),
            'is_search' => in_array($name, ['name', 'title', 'code', 'status'], true)
                || in_array($fieldMeta['form_component'], ['Select', 'ApiSelect'], true),
            'is_form' => ! $isPk && ! in_array($name, ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'], true) && ! $isJson,
            'form_component' => $fieldMeta['form_component'],
            'dict_code' => $fieldMeta['dict_code'],
            'form_options' => $fieldMeta['form_options'],
            'validation_rules' => $fieldMeta['validation_rules'],
            'sort' => $sort,
        ];
    }

    protected function inferTsType(string $type, bool $isStatus, ?string $comment, string $formComponent): string
    {
        if ($isStatus) {
            $profile = StatusFieldResolver::fromColumnTypeAndComment($type, $comment);

            return $profile?->tsType() ?? 'number';
        }

        if (in_array($formComponent, ['ApiSelect', 'ApiTreeSelect', 'InputNumber'], true)) {
            return 'number';
        }

        if ($formComponent === 'Switch') {
            return 'number';
        }

        if (preg_match('/\b(int|tinyint|smallint|mediumint|bigint)\b/', $type)) {
            return 'number';
        }

        if (preg_match('/\b(decimal|float|double)\b/', $type)) {
            return 'number';
        }

        if (str_contains($type, 'json')) {
            return 'Record<string, unknown>';
        }

        return 'string';
    }

    /**
     * @return list<string>
     */
    protected function inferValidationRules(string $name, string $type, bool $nullable, ?string $comment): array
    {
        if (in_array($name, ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'], true)) {
            return [];
        }

        $rules = [];
        if (! $nullable && $name !== 'id') {
            $rules[] = 'required';
        }

        if (preg_match('/varchar\((\d+)\)/', $type, $matches)) {
            $rules[] = 'max:'.$matches[1];
        }

        if ($name === 'status') {
            $profile = StatusFieldResolver::fromColumnTypeAndComment($type, $comment);
            if ($profile !== null) {
                $rules[] = $profile->laravelInRule();
            }
        }

        return $rules;
    }
}
