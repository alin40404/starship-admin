<?php

namespace Modules\Shop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'shop_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'sku_id',
        'product_name',
        'sku_code',
        'specs',
        'price',
        'quantity',
        'amount',
        'fulfillment_status',
    ];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'product_id' => 'integer',
            'sku_id' => 'integer',
            'specs' => 'array',
            'price' => 'integer',
            'quantity' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
