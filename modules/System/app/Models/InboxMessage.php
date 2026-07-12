<?php

namespace Modules\System\App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxMessage extends Model
{
    public const TYPE_SYSTEM = 'system';

    public const TYPE_TODO = 'todo';

    public const TYPE_ACCOUNT = 'account';

    protected $table = 'inbox_messages';

    protected $fillable = [
        'admin_id',
        'type',
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
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
