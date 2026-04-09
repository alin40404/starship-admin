<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasStarshipTable;

class DictItem extends Model
{
    use HasStarshipTable;
    use SoftDeletes;

    protected static function starshipTableSuffix(): string
    {
        return 'dict_items';
    }

    protected $guarded = [];

    public function dictType(): BelongsTo
    {
        return $this->belongsTo(DictType::class, 'dict_type_id');
    }
}
