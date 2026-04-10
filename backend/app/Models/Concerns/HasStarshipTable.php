<?php

namespace App\Models\Concerns;

use App\Support\StarshipTables;

trait HasStarshipTable
{
    /**
     * 不含前缀的表名片段，如 admins、dict_types。
     */
    abstract protected static function starshipTableSuffix(): string;

    public function getTable(): string
    {
        return StarshipTables::table(static::starshipTableSuffix());
    }
}
