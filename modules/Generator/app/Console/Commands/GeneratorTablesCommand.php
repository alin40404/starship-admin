<?php

namespace Modules\Generator\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Generator\App\Services\SchemaReader;

class GeneratorTablesCommand extends Command
{
    protected $signature = 'generator:tables {--keyword=}';

    protected $description = '列出数据库中可生成 CRUD 的物理表';

    public function handle(SchemaReader $schemaReader): int
    {
        $tables = $schemaReader->listTables($this->option('keyword'));

        if ($tables === []) {
            $this->warn('未找到匹配的表。');

            return self::SUCCESS;
        }

        $this->table(
            ['表名', '注释'],
            array_map(static fn (array $table): array => [
                $table['name'],
                $table['comment'] ?? '-',
            ], $tables),
        );

        $this->line('共 '.count($tables).' 张表');

        return self::SUCCESS;
    }
}
