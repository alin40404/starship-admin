<?php

namespace Starship\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $table = 'plugins';

    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
    ];
}
