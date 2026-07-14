<?php

namespace Modules\Member\App\Support;

/**
 * 会员管理（/member/members）按钮权限，与前端 constants/member-permissions.ts 保持一致。
 */
final class MemberPermissions
{
    public const QUERY = 'member.members.query';

    public const CREATE = 'member.members.create';

    public const UPDATE = 'member.members.update';

    public const STATUS = 'member.members.status';

    public const DELETE = 'member.members.delete';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Create', 'title' => '新增会员', 'permission' => self::CREATE, 'sort' => 2],
            ['name' => 'Update', 'title' => '编辑', 'permission' => self::UPDATE, 'sort' => 3],
            ['name' => 'Status', 'title' => '启停', 'permission' => self::STATUS, 'sort' => 4],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 5],
        ];
    }
}
