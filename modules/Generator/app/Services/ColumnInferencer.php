<?php

namespace Modules\Generator\App\Services;

use Modules\Generator\App\Support\ColumnLabelFormatter;
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
    public function inferMany(array $columns): array
    {
        $result = [];
        foreach ($columns as $index => $column) {
            $result[] = $this->infer($column, $index);
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
     * @return array<string, mixed>
     */
    public function infer(array $column, int $sort = 0): array
    {
        $name = $column['name'];
        $type = strtolower($column['type']);
        $isPk = $column['key'] === 'PRI';
        $isStatus = $name === 'status';
        $isJson = str_contains($type, 'json');
        $comment = $column['comment'] ?? null;

        return [
            'column_name' => $name,
            'column_type' => $column['type'],
            'column_comment' => ColumnLabelFormatter::fromComment($comment, $name),
            'ts_type' => $this->inferTsType($type, $isStatus, $comment),
            'is_pk' => $isPk,
            'is_list' => ! $isPk && ! in_array($name, ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'], true),
            'is_search' => in_array($name, ['name', 'title', 'code', 'status'], true),
            'is_form' => ! $isPk && ! in_array($name, ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'], true) && ! $isJson,
            'form_component' => $this->inferFormComponent($name, $type),
            'dict_code' => null,
            'validation_rules' => $this->inferValidationRules($name, $type, $column['nullable'], $comment),
            'sort' => $sort,
        ];
    }

    protected function inferTsType(string $type, bool $isStatus, ?string $comment): string
    {
        if ($isStatus) {
            $profile = StatusFieldResolver::fromColumnTypeAndComment($type, $comment);

            return $profile?->tsType() ?? 'number';
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

    protected function inferFormComponent(string $name, string $type): string
    {
        if ($name === 'status') {
            return 'Switch';
        }

        if ($name === 'remark' || str_contains($type, 'text')) {
            return 'Textarea';
        }

        if (preg_match('/\b(int|tinyint|smallint|mediumint|bigint|decimal|float|double)\b/', $type)) {
            return 'InputNumber';
        }

        if (preg_match('/\b(date|datetime|timestamp)\b/', $type)) {
            return 'DatePicker';
        }

        return 'Input';
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
