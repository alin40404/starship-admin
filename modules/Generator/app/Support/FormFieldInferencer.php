<?php

namespace Modules\Generator\App\Support;

use Modules\Generator\App\Services\SchemaReader;

/**
 * FastAdmin 风格字段推断：数据库类型 + 字段名 + 注释 → 表单组件与 form_options。
 */
final class FormFieldInferencer
{
    public function __construct(
        protected SchemaReader $schemaReader,
        protected RelationFieldResolver $relationFieldResolver,
    ) {}

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
     * @return array{
     *   form_component: string,
     *   dict_code: string|null,
     *   form_options: array<string, mixed>|null,
     *   validation_rules: list<string>
     * }
     */
    public function infer(array $column, string $tableName, array $foreignKeys, array $baseRules): array
    {
        $name = $column['name'];
        $type = strtolower($column['type']);
        $comment = $column['comment'] ?? null;

        if ($name === 'status') {
            return [
                'form_component' => 'Switch',
                'dict_code' => null,
                'form_options' => null,
                'validation_rules' => $baseRules,
            ];
        }

        $upload = UploadFieldResolver::resolve($name, $type, $comment);
        if ($upload !== null) {
            return [
                'form_component' => $upload['component'],
                'dict_code' => null,
                'form_options' => $upload,
                'validation_rules' => $this->appendUploadRules($baseRules, $upload, $type),
            ];
        }

        if (EditorFieldResolver::shouldUseEditor($name, $type)) {
            return [
                'form_component' => 'RichEditor',
                'dict_code' => null,
                'form_options' => ['kind' => 'editor'],
                'validation_rules' => $baseRules,
            ];
        }

        $dictCode = EnumFieldResolver::parseDictCodeFromComment($comment);
        if ($dictCode !== null) {
            return [
                'form_component' => 'Select',
                'dict_code' => $dictCode,
                'form_options' => [
                    'kind' => 'dict',
                    'dict_code' => $dictCode,
                ],
                'validation_rules' => $baseRules,
            ];
        }

        $enumValues = EnumFieldResolver::parseEnumColumnType($type);
        if ($enumValues !== null) {
            $options = EnumFieldResolver::parseOptionsFromComment($comment)
                ?? EnumFieldResolver::valuesToOptions($enumValues);
            $useRadio = count($options) <= 4;

            return [
                'form_component' => $useRadio ? 'RadioGroup' : 'Select',
                'dict_code' => null,
                'form_options' => [
                    'kind' => 'enum',
                    'options' => $options,
                    'display' => $useRadio ? 'radio' : 'select',
                ],
                'validation_rules' => $this->appendInRule($baseRules, array_column($options, 'value')),
            ];
        }

        if (preg_match('/\bset\(/i', $type)) {
            $values = EnumFieldResolver::parseEnumColumnType(str_replace('set', 'enum', $type)) ?? [];
            $options = EnumFieldResolver::parseOptionsFromComment($comment)
                ?? EnumFieldResolver::valuesToOptions($values);

            return [
                'form_component' => 'CheckboxGroup',
                'dict_code' => null,
                'form_options' => [
                    'kind' => 'enum',
                    'multiple' => true,
                    'options' => $options,
                ],
                'validation_rules' => $baseRules,
            ];
        }

        $commentOptions = EnumFieldResolver::parseOptionsFromComment($comment);
        if ($commentOptions !== null && count($commentOptions) >= 2 && $name !== 'status') {
            $isMultiple = str_contains((string) $comment, '多选')
                || str_contains((string) $comment, '(多选)');
            $useRadio = ! $isMultiple && count($commentOptions) <= 4;

            return [
                'form_component' => $isMultiple ? 'CheckboxGroup' : ($useRadio ? 'RadioGroup' : 'Select'),
                'dict_code' => null,
                'form_options' => [
                    'kind' => 'enum',
                    'options' => $commentOptions,
                    'multiple' => $isMultiple,
                    'display' => $isMultiple ? 'checkbox' : ($useRadio ? 'radio' : 'select'),
                ],
                'validation_rules' => $this->appendInRule($baseRules, array_column($commentOptions, 'value')),
            ];
        }

        $relation = $this->relationFieldResolver->resolve($name, $tableName, $foreignKeys);
        if ($relation !== null) {
            return [
                'form_component' => $relation['component'],
                'dict_code' => null,
                'form_options' => array_merge(['kind' => 'relation'], $relation),
                'validation_rules' => array_merge($baseRules, ['integer']),
            ];
        }

        $basic = BasicFieldResolver::resolve($name, $type, $comment, $baseRules);
        if ($basic !== null) {
            return $basic;
        }

        return [
            'form_component' => $this->inferBasicComponent($name, $type),
            'dict_code' => null,
            'form_options' => null,
            'validation_rules' => $baseRules,
        ];
    }

    protected function inferBasicComponent(string $name, string $type): string
    {
        if ($name === 'remark' || in_array($name, ['summary', 'description', 'intro'], true) || str_contains($type, 'text')) {
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
     * @param  list<string>  $rules
     * @return list<string>
     */
    protected function appendInRule(array $rules, array $values): array
    {
        $rules = array_values(array_filter($rules, static fn (string $rule): bool => ! str_starts_with($rule, 'in:')));

        if ($values !== []) {
            $rules[] = 'in:'.implode(',', array_map('strval', $values));
        }

        return $rules;
    }

    /**
     * @param  list<string>  $rules
     * @param  array{kind: string, max_count?: int}  $upload
     * @return list<string>
     */
    protected function appendUploadRules(array $rules, array $upload, string $columnType): array
    {
        $kind = $upload['kind'];
        if (! in_array($kind, ['image', 'attachment', 'file'], true)) {
            return array_values(array_unique($rules));
        }

        $rules = array_values(array_filter(
            $rules,
            static fn (string $rule): bool => ! str_starts_with($rule, 'max:'),
        ));
        $rules[] = 'string';
        $rules[] = 'max:'.$this->resolveUploadMaxLength($upload, $columnType);

        return array_values(array_unique($rules));
    }

    /**
     * @param  array{kind: string, max_count?: int}  $upload
     */
    protected function resolveUploadMaxLength(array $upload, string $columnType): int
    {
        $type = strtolower($columnType);

        if (preg_match('/varchar\((\d+)\)/', $type, $matches)) {
            return max(1, (int) $matches[1]);
        }

        if (str_contains($type, 'longtext')) {
            return 65535;
        }

        // text / 多图多附件：放宽到 16KB，避免逗号拼接 URL 被 max:500 误杀
        if (
            str_contains($type, 'text')
            || ! isset($upload['max_count'])
            || (int) ($upload['max_count'] ?? 1) > 1
        ) {
            return 16384;
        }

        return $upload['kind'] === 'image' ? 500 : 1000;
    }
}
