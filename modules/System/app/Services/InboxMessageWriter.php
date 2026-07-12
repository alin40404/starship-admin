<?php

namespace Modules\System\App\Services;

use Modules\System\App\Models\InboxMessage;
use Modules\System\App\Support\InboxConfigRegistrar;

class InboxMessageWriter
{
    public function send(
        int $adminId,
        string $type,
        string $title,
        ?string $content = null,
        ?string $link = null,
    ): ?InboxMessage {
        if (! InboxConfigRegistrar::isEnabled()) {
            return null;
        }

        if (! InboxConfigRegistrar::allowsType($type)) {
            return null;
        }

        $title = trim($title);
        if ($title === '') {
            return null;
        }

        return InboxMessage::query()->create([
            'admin_id' => $adminId,
            'type' => $type,
            'title' => $title,
            'content' => $content !== null ? trim($content) : null,
            'link' => $link !== null ? trim($link) : null,
            'is_read' => 0,
        ]);
    }
}
