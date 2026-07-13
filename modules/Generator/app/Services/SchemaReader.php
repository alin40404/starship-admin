<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\DB;

class SchemaReader
{
    /**
     * @return list<array{name: string, comment: string|null}>
     */
    public function listTables(?string $keyword = null): array
    {
        $database = $this->databaseName();
        $rows = DB::select(
            'SELECT TABLE_NAME AS name, TABLE_COMMENT AS comment
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?
             ORDER BY TABLE_NAME',
            [$database, 'BASE TABLE'],
        );

        $tables = array_map(static function (object $row): array {
            return [
                'name' => (string) $row->name,
                'comment' => $row->comment !== '' ? (string) $row->comment : null,
            ];
        }, $rows);

        if ($keyword) {
            $keyword = strtolower($keyword);
            $tables = array_values(array_filter(
                $tables,
                static fn (array $table): bool => str_contains(strtolower($table['name']), $keyword),
            ));
        }

        return $tables;
    }

    /**
     * @return list<array{
     *   name: string,
     *   type: string,
     *   nullable: bool,
     *   key: string,
     *   default: mixed,
     *   extra: string,
     *   comment: string|null
     * }>
     */
    public function listColumns(string $tableName): array
    {
        $tableName = $this->physicalTableName($tableName);
        $database = $this->databaseName();
        $rows = DB::select(
            'SELECT COLUMN_NAME AS name,
                    COLUMN_TYPE AS type,
                    IS_NULLABLE AS nullable,
                    COLUMN_KEY AS column_key,
                    COLUMN_DEFAULT AS default_value,
                    EXTRA AS extra,
                    COLUMN_COMMENT AS comment
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$database, $tableName],
        );

        return array_map(static function (object $row): array {
            return [
                'name' => (string) $row->name,
                'type' => (string) $row->type,
                'nullable' => strtoupper((string) $row->nullable) === 'YES',
                'key' => (string) $row->column_key,
                'default' => $row->default_value,
                'extra' => (string) $row->extra,
                'comment' => $row->comment !== '' ? (string) $row->comment : null,
            ];
        }, $rows);
    }

    public function tableExists(string $tableName): bool
    {
        return DB::getSchemaBuilder()->hasTable($this->logicalTableName($tableName));
    }

    /**
     * 去掉 DB 前缀后的逻辑表名（demo_products），供 Eloquent / Schema 使用。
     */
    public function logicalTableName(string $tableName): string
    {
        $tableName = trim($tableName);
        $prefix = (string) DB::connection()->getTablePrefix();

        if ($prefix !== '' && str_starts_with($tableName, $prefix)) {
            return substr($tableName, strlen($prefix));
        }

        return $tableName;
    }

    /**
     * 解析逻辑表名：支持省略或包含 DB 前缀（sa_article / article → demo_products）。
     */
    public function resolveTableName(string $tableName): string
    {
        $logical = $this->logicalTableName($tableName);

        if ($this->tableExists($logical)) {
            return $logical;
        }

        return $logical;
    }

    /**
     * information_schema 查询使用的物理表名（含 DB 前缀）。
     */
    public function physicalTableName(string $tableName): string
    {
        $logical = $this->logicalTableName($tableName);
        $prefix = (string) DB::connection()->getTablePrefix();

        if ($prefix === '') {
            return $logical;
        }

        return $prefix.$logical;
    }

    public function tableComment(string $tableName): ?string
    {
        $tableName = $this->physicalTableName($tableName);

        foreach ($this->listTables() as $table) {
            if ($table['name'] === $tableName) {
                return $table['comment'];
            }
        }

        return null;
    }

    protected function databaseName(): string
    {
        return (string) DB::connection()->getDatabaseName();
    }
}
