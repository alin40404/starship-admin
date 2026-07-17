<?php

namespace Modules\GameShop\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use SoftDeletes;

    protected $table = 'game_shop_games';

    protected $fillable = [
        'name',
        'code',
        'cover',
        'platform',
        'publisher',
        'status',
        'sort',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(GameProduct::class, 'game_id');
    }
}
