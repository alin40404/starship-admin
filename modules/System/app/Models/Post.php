<?php

namespace Modules\System\App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasStarshipTable;
use App\Support\StarshipTables;

class Post extends Model
{
    use HasStarshipTable;
    use SoftDeletes;

    protected static function starshipTableSuffix(): string
    {
        return 'posts';
    }

    protected $guarded = [];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, StarshipTables::table('admin_post'), 'post_id', 'admin_id');
    }
}
