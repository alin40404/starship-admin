<?php

namespace Modules\System\App\Support;

/**
 * 附件管理（/resource/attachments）按钮权限，与前端 constants/attachment-permissions.ts 保持一致。
 */
final class AttachmentPermissions
{
    public const QUERY = 'system.attachments.query';

    public const UPLOAD = 'system.attachments.upload';

    public const DELETE = 'system.attachments.delete';

    public const BATCH_DELETE = 'system.attachments.batch_delete';

    /**
     * @return list<array{name: string, title: string, permission: string, sort: int}>
     */
    public static function buttonDefinitions(): array
    {
        return [
            ['name' => 'Query', 'title' => '查询', 'permission' => self::QUERY, 'sort' => 1],
            ['name' => 'Upload', 'title' => '上传附件', 'permission' => self::UPLOAD, 'sort' => 2],
            ['name' => 'Delete', 'title' => '删除', 'permission' => self::DELETE, 'sort' => 3],
            ['name' => 'BatchDelete', 'title' => '批量删除', 'permission' => self::BATCH_DELETE, 'sort' => 4],
        ];
    }
}
