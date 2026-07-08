<?php

namespace Modules\Generator\App\Support;

/**
 * 代码生成器权限，与前端 constants/generator-permissions.ts 保持一致。
 */
final class GeneratorPermissions
{
    /** 菜单页访问（/dev/codegen，与菜单 permission 一致） */
    public const MANAGE = 'generator.codegen';

    public const QUERY = 'generator.tables.query';

    public const IMPORT = 'generator.tables.import';

    public const UPDATE = 'generator.tables.update';

    public const DELETE = 'generator.tables.delete';

    public const PREVIEW = 'generator.codegen.preview';

    public const GENERATE = 'generator.codegen.generate';

    public const RECORDS_QUERY = 'generator.records.query';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Import', 'title' => '导入表', 'permission' => self::IMPORT, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑配置', 'permission' => self::UPDATE, 'sort' => 3],
            ['name' => 'Delete', 'title' => '删除配置', 'permission' => self::DELETE, 'sort' => 4],
            ['name' => 'Preview', 'title' => '预览代码', 'permission' => self::PREVIEW, 'sort' => 5],
            ['name' => 'Generate', 'title' => '执行生成', 'permission' => self::GENERATE, 'sort' => 6],
            ['name' => 'RecordsQuery', 'title' => '生成记录', 'permission' => self::RECORDS_QUERY, 'sort' => 7],
        ];
    }
}
