<?php

namespace Modules\Shop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSku extends Model
{
    use SoftDeletes;

    protected $table = 'shop_product_skus';

    protected $fillable = [
        'product_id',
        'sku_code',
        'specs',
        'price',
        'compare_at_price',
        'stock',
        'status',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'specs' => 'array',
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'stock' => 'integer',
            'status' => 'integer',
            'extra' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
