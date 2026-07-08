<?php

namespace Modules\Monitor\App\Support;

final class ExceptionLogPermissions
{
    public const MANAGE = 'monitor.exception_logs';

    public const QUERY = 'monitor.exception_logs.query';

    public const DELETE = 'monitor.exception_logs.delete';

    public const BATCH_DELETE = 'monitor.exception_logs.batch_delete';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 2],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 3],
        ];
    }
}
