<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DictItem extends Model
{
    use SoftDeletes;

    protected $table = 'dict_items';

    protected $guarded = [];

    public function dictType(): BelongsTo
    {
        return $this->belongsTo(DictType::class, 'dict_type_id');
    }
}
