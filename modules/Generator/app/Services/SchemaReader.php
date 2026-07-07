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
        return DB::getSchemaBuilder()->hasTable($tableName);
    }

    public function tableComment(string $tableName): ?string
    {
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
