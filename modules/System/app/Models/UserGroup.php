<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasStarshipTable;
use App\Support\StarshipTables;

class UserGroup extends Model
{
    use HasStarshipTable;
    use SoftDeletes;

    protected static function starshipTableSuffix(): string
    {
        return 'user_groups';
    }

    protected $guarded = [];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            Admin::class,
            StarshipTables::table('admin_user_group'),
            'user_group_id',
            'admin_id',
        );
    }
}
