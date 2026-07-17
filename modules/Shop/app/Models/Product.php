<?php

namespace Modules\Shop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\System\App\Models\Category;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'shop_products';

    protected $fillable = [
        'category_id',
        'brand_id',
        'product_type',
        'fulfillment_type',
        'name',
        'subtitle',
        'price',
        'stock',
        'cover',
        'images',
        'description',
        'status',
        'sort',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'brand_id' => 'integer',
            'price' => 'integer',
            'stock' => 'integer',
            'images' => 'array',
            'status' => 'integer',
            'sort' => 'integer',
            'extra' => 'array',
        ];
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class, 'product_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function defaultSku(): ?ProductSku
    {
        return $this->skus()->orderBy('id')->first();
    }
}
