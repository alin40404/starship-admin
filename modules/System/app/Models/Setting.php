<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStarshipTable;

/**
 * 系统配置项（物理表：{prefix}configs）
 */
class Setting extends Model
{
    use HasStarshipTable;

    protected static function starshipTableSuffix(): string
    {
        return 'configs';
    }

    protected $fillable = [
        'group_name',
        'name',
        'key',
        'value',
        'type',
        'sort',
        'remark',
    ];
}
