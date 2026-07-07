<?php

namespace Modules\Generator\App\Models;

use App\Models\Concerns\HasStarshipTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratorColumn extends Model
{
    use HasStarshipTable;

    protected $casts = [
        'is_pk' => 'boolean',
        'is_list' => 'boolean',
        'is_search' => 'boolean',
        'is_form' => 'boolean',
        'validation_rules' => 'array',
    ];

    protected $guarded = [];

    protected static function starshipTableSuffix(): string
    {
        return 'generator_columns';
    }

    public function tableConfig(): BelongsTo
    {
        return $this->belongsTo(GeneratorTable::class, 'generator_table_id');
    }
}
