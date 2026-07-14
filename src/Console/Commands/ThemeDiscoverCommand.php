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

        $this->components->info('主题发现完成');
        $this->components->twoColumnDetail('扫描到', (string) $result['discovered']);
        $this->components->twoColumnDetail('新建', (string) $result['created']);
        $this->components->twoColumnDetail('更新', (string) $result['updated']);

        return self::SUCCESS;
    }
}
