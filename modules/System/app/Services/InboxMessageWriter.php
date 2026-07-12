<?php

namespace Modules\System\App\Services;

use Modules\System\App\Models\InboxMessage;
use Modules\System\App\Support\InboxConfigRegistrar;

class InboxMessageWriter
{
    /**
     * @param  array{
     *     category?: string,
     *     sub_type?: string|null,
     *     priority?: string,
     *     biz_type?: string|null,
     *     biz_id?: int|null,
     *     sender_id?: int|null,
     *     payload?: array<string, mixed>|null,
     *     status?: string|null,
     *     action_deadline?: \DateTimeInterface|string|null,
     *     group_key?: string|null,
     * }  $extra
     */
    public function send(
        int $adminId,
        string $type,
        string $title,
        ?string $content = null,
        ?string $link = null,
        array $extra = [],
    ): ?InboxMessage {
        if (! InboxConfigRegistrar::isEnabled()) {
            return null;
        }

        $category = (string) ($extra['category'] ?? InboxMessage::categoryFromType($type));
        if (! InboxConfigRegistrar::isCategoryVisible($category)) {
            return null;
        }

        $title = trim($title);
        if ($title === '') {
            return null;
        }

        $priority = (string) ($extra['priority'] ?? InboxMessage::PRIORITY_NORMAL);

        return InboxMessage::query()->create([
            'admin_id' => $adminId,
            'type' => $type,
            'category' => $category,
            'sub_type' => $extra['sub_type'] ?? null,
            'priority' => $priority,
            'biz_type' => $extra['biz_type'] ?? null,
            'biz_id' => $extra['biz_id'] ?? null,
            'sender_id' => $extra['sender_id'] ?? null,
            'payload' => $extra['payload'] ?? null,
            'status' => $extra['status'] ?? null,
            'action_deadline' => $extra['action_deadline'] ?? null,
            'group_key' => $extra['group_key'] ?? null,
            'title' => $title,
            'content' => $content !== null ? trim($content) : null,
            'link' => $link !== null ? trim($link) : null,
            'is_read' => 0,
        ]);
    }
}
