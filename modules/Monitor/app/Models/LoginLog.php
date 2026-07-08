<?php

namespace Modules\Monitor\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\System\App\Models\Admin;

class LoginLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'monitor_login_logs';

    protected $guarded = [];

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
