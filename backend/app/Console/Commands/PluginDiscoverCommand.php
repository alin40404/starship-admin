<?php

namespace App\Console\Commands;

use App\Foundation\Plugin\PluginLifecycle;
use App\Foundation\Plugin\PluginManager;
use Illuminate\Console\Command;

class PluginDiscoverCommand extends Command
{
    protected $signature = 'starship:plugin:discover';

    protected $description = '扫描 plugins/ 目录并同步插件注册表';

    public function handle(PluginLifecycle $pluginLifecycle): int
    {
        $result = $pluginLifecycle->discover();

        $this->info("发现 {$result['discovered']} 个插件，新建 {$result['created']} 个，更新 {$result['updated']} 个");

        return self::SUCCESS;
    }
}
