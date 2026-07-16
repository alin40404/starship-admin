<?php

namespace Modules\Cms\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'cms_categories';

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'sort',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort' => 'integer',
            'status' => 'integer',
        ];
    }
}
