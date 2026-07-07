<?php

namespace Modules\Generator\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratorRecord extends Model
{

    protected $casts = [
        'files' => 'array',
        'options' => 'array',
    ];

    protected $guarded = [];

    protected $table = 'generator_records';

    public function tableConfig(): BelongsTo
    {
        return $this->belongsTo(GeneratorTable::class, 'generator_table_id');
    }
}
