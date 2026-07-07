<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserGroup extends Model
{
    use SoftDeletes;

    protected $table = 'user_groups';

    protected $guarded = [];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            Admin::class,
            'admin_user_group',
            'user_group_id',
            'admin_id',
        );
    }
}
