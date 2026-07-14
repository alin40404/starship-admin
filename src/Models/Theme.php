<?php

namespace Starship\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $table = 'themes';

    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'integer',
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
    ];
}
