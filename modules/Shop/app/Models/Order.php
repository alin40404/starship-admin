<?php

namespace Modules\Shop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 0;

    public const STATUS_PAID = 1;

    public const STATUS_CANCELLED = 2;

    public const STATUS_REFUNDED = 3;

    public const FULFILLMENT_PENDING = 'pending';

    public const FULFILLMENT_SHIPPED = 'shipped';

    public const FULFILLMENT_FULFILLED = 'fulfilled';

    public const FULFILLMENT_FAILED = 'failed';

    protected $table = 'shop_orders';

    protected $fillable = [
        'order_no',
        'member_id',
        'product_id',
        'sku_id',
        'product_name',
        'amount',
        'quantity',
        'status',
        'pay_channel',
        'prepay_id',
        'trade_no',
        'paid_at',
        'refunded_at',
        'remark',
        'fulfillment_type',
        'fulfillment_payload',
        'fulfillment_status',
    ];

    protected function casts(): array
    {
        return [
            'member_id' => 'integer',
            'product_id' => 'integer',
            'sku_id' => 'integer',
            'amount' => 'integer',
            'quantity' => 'integer',
            'status' => 'integer',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'fulfillment_payload' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
