<?php

namespace Modules\GameShop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameOrder extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 0;

    public const STATUS_PAID = 1;

    public const STATUS_DONE = 2;

    public const STATUS_FAILED = 3;

    public const STATUS_REFUNDED = 4;

    protected $table = 'game_shop_orders';

    protected $fillable = [
        'order_no',
        'member_id',
        'game_id',
        'product_id',
        'product_name',
        'amount',
        'quantity',
        'status',
        'pay_channel',
        'trade_no',
        'paid_at',
        'player_account',
        'server_name',
        'role_id',
        'fulfill_status',
        'fulfill_payload',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'member_id' => 'integer',
            'game_id' => 'integer',
            'product_id' => 'integer',
            'amount' => 'integer',
            'quantity' => 'integer',
            'status' => 'integer',
            'paid_at' => 'datetime',
            'fulfill_payload' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(GameProduct::class, 'product_id');
    }
}
