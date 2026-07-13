<?php

namespace Modules\Generator\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Generator\App\Services\ColumnInferencer;
use Modules\Generator\App\Services\SchemaReader;

class GeneratorInspectCommand extends Command
{
    protected $signature = 'starship:generator:inspect {table : 物理表名，如 sa_article}';

    protected $description = '查看表字段及智能推断的生成配置';

    public function handle(SchemaReader $schemaReader, ColumnInferencer $columnInferencer): int
    {
        $table = (string) $this->argument('table');

        if (! $schemaReader->tableExists($table)) {
            $this->error("数据表不存在：{$table}");

            return self::FAILURE;
        }

        $tableName = $schemaReader->resolveTableName($table);
        $columns = $schemaReader->listColumns($table);
        $inferred = $columnInferencer->inferMany($columns, $tableName);

        $this->info("表：{$table}");
        $comment = $schemaReader->tableComment($table);
        if ($comment) {
            $this->line("注释：{$comment}");
        }

        $this->newLine();
        $this->table(
            ['字段', '类型', '列表', '搜索', '表单', '组件', '注释'],
            array_map(static fn (array $column): array => [
                $column['column_name'],
                $column['column_type'],
                $column['is_list'] ? 'Y' : 'N',
                $column['is_search'] ? 'Y' : 'N',
                $column['is_form'] ? 'Y' : 'N',
                $column['form_component'],
                $column['column_comment'],
            ], $inferred),
        );

        return self::SUCCESS;
    }
}
