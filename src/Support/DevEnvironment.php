<?php

namespace Starship\Core\Support;

final class DevEnvironment
{
    /**
     * 是否为开发环境（local / development）。
     */
    public static function isDevelopment(): bool
    {
        return app()->environment(['local', 'development']);
    }
}
