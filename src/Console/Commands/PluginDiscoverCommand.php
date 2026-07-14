<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Starship\Core\Plugin\PluginLifecycle;

class PluginDiscoverCommand extends Command
{
    protected $signature = 'starship:plugin:discover';

    protected $description = '批量扫描 plugins/ 并同步注册表（安装不必先执行）';

    public function handle(PluginLifecycle $pluginLifecycle): int
    {
        $result = $pluginLifecycle->discover();

        $this->info("发现 {$result['discovered']} 个插件，新建 {$result['created']} 个，更新 {$result['updated']} 个");

        return self::SUCCESS;
    }
}
