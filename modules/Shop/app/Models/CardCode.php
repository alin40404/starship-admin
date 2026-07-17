<?php

namespace Modules\Shop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardCode extends Model
{
    public const STATUS_AVAILABLE = 0;

    public const STATUS_SOLD = 1;

    public const STATUS_VOID = 2;

    protected $table = 'shop_card_codes';

    protected $fillable = [
        'sku_id',
        'code',
        'status',
        'order_id',
        'order_item_id',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'sku_id' => 'integer',
            'status' => 'integer',
            'order_id' => 'integer',
            'order_item_id' => 'integer',
            'sold_at' => 'datetime',
        ];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }
}
