<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DictType extends Model
{
    use SoftDeletes;

    protected $table = 'dict_types';

    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(DictItem::class, 'dict_type_id');
    }
}
