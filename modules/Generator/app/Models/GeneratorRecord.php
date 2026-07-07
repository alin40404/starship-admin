<?php

namespace Modules\Generator\App\Models;

use App\Models\Concerns\HasStarshipTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratorRecord extends Model
{
    use HasStarshipTable;

    protected $casts = [
        'files' => 'array',
        'options' => 'array',
    ];

    protected $guarded = [];

    protected static function starshipTableSuffix(): string
    {
        return 'generator_records';
    }

    public function tableConfig(): BelongsTo
    {
        return $this->belongsTo(GeneratorTable::class, 'generator_table_id');
    }
}
