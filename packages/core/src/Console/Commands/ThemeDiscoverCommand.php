<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Starship\Core\Theme\ThemeLifecycle;

class ThemeDiscoverCommand extends Command
{
    protected $signature = 'starship:theme:discover';

    protected $description = '扫描 themes/ 目录并同步主题注册表';

    public function handle(ThemeLifecycle $themeLifecycle): int
    {
        $result = $themeLifecycle->discover();

        $this->info("发现 {$result['discovered']} 个主题，新建 {$result['created']} 个，更新 {$result['updated']} 个");

        return self::SUCCESS;
    }
}
