<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{

    protected $table = 'categories';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (Category $category) {
            if ((int) $category->sort === 0) {
                $category->updateQuietly(['sort' => $category->id]);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
