<?php

namespace Modules\Shop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $table = 'shop_carts';

    protected $fillable = [
        'member_id',
    ];

    protected function casts(): array
    {
        return [
            'member_id' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }
}
