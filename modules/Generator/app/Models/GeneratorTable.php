<?php

namespace Modules\Generator\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneratorTable extends Model
{

    protected $casts = [
        'options' => 'array',
        'generated_at' => 'datetime',
    ];

    protected $guarded = [];

    protected $table = 'generator_tables';

    public function columns(): HasMany
    {
        return $this->hasMany(GeneratorColumn::class)->orderBy('sort');
    }

    public function records(): HasMany
    {
        return $this->hasMany(GeneratorRecord::class)->latest('id');
    }
}
