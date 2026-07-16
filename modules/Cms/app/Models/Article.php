<?php

namespace Modules\Cms\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\System\App\Models\Category;

class Article extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 0;

    public const STATUS_PUBLISHED = 1;

    protected $table = 'cms_articles';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'summary',
        'content',
        'cover',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'status' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
