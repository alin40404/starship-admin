<?php

namespace Starship\Core\Console\Commands;

use InvalidArgumentException;
use Starship\Core\Plugin\PluginLifecycle;
use Starship\Core\Plugin\PluginManager;

class PluginInstallCommand extends PluginActionCommand
{
    protected $signature = 'starship:plugin:install {plugin : 插件目录名或 slug}';

    protected $description = '安装本地插件';

    public function handle(PluginManager $pluginManager, PluginLifecycle $pluginLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($pluginManager);
            $pluginLifecycle->install($slug);
            $this->info("插件 {$slug} 安装成功");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
