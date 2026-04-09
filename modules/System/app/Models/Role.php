<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasStarshipTable;
use App\Support\StarshipTables;

class Role extends Model
{
    use HasStarshipTable;
    use SoftDeletes;

    protected static function starshipTableSuffix(): string
    {
        return 'roles';
    }

    protected $guarded = [];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, StarshipTables::table('admin_role'), 'role_id', 'admin_id');
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, StarshipTables::table('role_menu'), 'role_id', 'menu_id');
    }
}
