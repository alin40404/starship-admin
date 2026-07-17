<?php

namespace Modules\GameShop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameProduct extends Model
{
    use SoftDeletes;

    protected $table = 'game_shop_products';

    protected $fillable = [
        'game_id',
        'name',
        'sku_code',
        'product_kind',
        'price',
        'stock',
        'cover',
        'description',
        'extra',
        'status',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'game_id' => 'integer',
            'price' => 'integer',
            'stock' => 'integer',
            'extra' => 'array',
            'status' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
