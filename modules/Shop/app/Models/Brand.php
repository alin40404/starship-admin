<?php

namespace Modules\Shop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    protected $table = 'shop_brands';

    protected $fillable = [
        'name',
        'logo',
        'sort',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'status' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
