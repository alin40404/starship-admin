<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExceptionLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'monitor_exception_logs';

    protected $guarded = [];

    protected $casts = [
        'trace_id' => 'string',
    ];

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
