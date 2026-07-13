<?php

namespace Modules\Generator\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratorColumn extends Model
{

    protected $casts = [
        'is_pk' => 'boolean',
        'is_list' => 'boolean',
        'is_search' => 'boolean',
        'is_form' => 'boolean',
        'validation_rules' => 'array',
        'form_options' => 'array',
    ];

    protected $guarded = [];

    protected $table = 'generator_columns';

    public function tableConfig(): BelongsTo
    {
        return $this->belongsTo(GeneratorTable::class, 'generator_table_id');
    }
}
