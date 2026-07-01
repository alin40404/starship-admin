<?php

namespace Modules\System\App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Concerns\HasStarshipTable;
use App\Support\StarshipTables;

class Admin extends Authenticatable
{
    use HasApiTokens;
    use HasStarshipTable;
    use SoftDeletes;

    protected static function starshipTableSuffix(): string
    {
        return 'admins';
    }

    protected $fillable = [
        'username',
        'nickname',
        'real_name',
        'password',
        'avatar',
        'email',
        'mobile',
        'dept_id',
        'status',
        'is_super',
        'remark',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, StarshipTables::table('admin_role'), 'admin_id', 'role_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, StarshipTables::table('admin_post'), 'admin_id', 'post_id');
    }

    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            UserGroup::class,
            StarshipTables::table('admin_user_group'),
            'admin_id',
            'user_group_id',
        );
    }
}
