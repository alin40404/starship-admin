<?php

namespace Modules\System\App\Models;

use App\Models\Concerns\HasStarshipTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasStarshipTable;

    public $timestamps = false;

    protected static function starshipTableSuffix(): string
    {
        return 'areas';
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pid' => 'integer',
            'level' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pid');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'pid');
    }
}
