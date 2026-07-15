<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $table = 'posts';

    protected $guarded = [];

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_post', 'post_id', 'admin_id');
    }
}
