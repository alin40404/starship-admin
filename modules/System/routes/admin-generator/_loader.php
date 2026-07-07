<?php

declare(strict_types=1);

/**
 * 自动加载本目录下的 codegen 管理端路由（跳过 _loader.php 自身）。
 */

foreach (glob(__DIR__.'/*.php') ?: [] as $adminRouteFile) {
    if (basename($adminRouteFile) === '_loader.php') {
        continue;
    }

    require $adminRouteFile;
}
