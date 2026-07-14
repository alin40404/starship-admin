<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Starship\Core\Plugin\PluginLifecycle;

class PluginDiscoverCommand extends Command
{
    protected $signature = 'starship:plugin:discover';

    protected $description = '扫描 plugins/ 目录并同步插件注册表';

    public function handle(PluginLifecycle $pluginLifecycle): int
    {
        $result = $pluginLifecycle->discover();

        $this->components->info('插件发现完成');
        $this->components->twoColumnDetail('扫描到', (string) $result['discovered']);
        $this->components->twoColumnDetail('新建', (string) $result['created']);
        $this->components->twoColumnDetail('更新', (string) $result['updated']);

        return self::SUCCESS;
    }
}
