<?php

namespace Modules\System\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 系统配置项（物理表：{prefix}configs）
 */
class Setting extends Model
{

    protected $table = 'configs';

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
