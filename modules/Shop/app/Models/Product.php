<?php

namespace Modules\Shop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'shop_products';

    protected $fillable = [
        'name',
        'price',
        'stock',
        'cover',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'status' => 'integer',
        ];
    }
}
