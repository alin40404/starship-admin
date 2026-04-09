<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\HasStarshipTable;

class Attachment extends Model
{
    use HasStarshipTable;

    protected static function starshipTableSuffix(): string
    {
        return 'attachments';
    }

    protected $guarded = [];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
