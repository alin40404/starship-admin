<?php

namespace App\Support;

final class StarshipTables
{
    public static function prefix(): string
    {
        return (string) config('starship.table_prefix', 'sa_');
    }

    /**
     * @param  non-empty-string  $nameWithoutPrefix  不含前缀的表名，如 admins、admin_role
     */
    public static function table(string $nameWithoutPrefix): string
    {
        return static::prefix().$nameWithoutPrefix;
    }
}
