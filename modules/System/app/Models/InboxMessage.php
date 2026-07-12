<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxMessage extends Model
{
    public const TYPE_SYSTEM = 'system';

    public const TYPE_TODO = 'todo';

    public const TYPE_ACCOUNT = 'account';

    public const CATEGORY_NOTICE = 'notice';

    public const CATEGORY_APPROVAL = 'approval';

    public const CATEGORY_TASK = 'task';

    public const CATEGORY_CRM = 'crm';

    public const CATEGORY_ALERT = 'alert';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'inbox_messages';

    protected $fillable = [
        'admin_id',
        'type',
        'category',
        'sub_type',
        'priority',
        'biz_type',
        'biz_id',
        'sender_id',
        'payload',
        'status',
        'action_deadline',
        'group_key',
        'title',
        'content',
        'link',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'integer',
            'read_at' => 'datetime',
            'action_deadline' => 'datetime',
            'payload' => 'array',
            'biz_id' => 'integer',
            'sender_id' => 'integer',
        ];
    }

    public static function categoryFromType(string $type): string
    {
        return match ($type) {
            self::TYPE_TODO => self::CATEGORY_TASK,
            self::TYPE_ACCOUNT => self::CATEGORY_ALERT,
            default => self::CATEGORY_NOTICE,
        };
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sender_id');
    }
}
