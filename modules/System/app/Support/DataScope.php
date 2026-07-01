<?php

namespace Modules\System\App\Support;

/**
 * 角色数据范围，与前端 DATA_SCOPE_OPTIONS 保持一致。
 */
final class DataScope
{
    public const ALL = 1;

    public const DEPT = 2;

    public const DEPT_AND_CHILD = 3;

    public const SELF = 4;

    public const CUSTOM = 5;
}
