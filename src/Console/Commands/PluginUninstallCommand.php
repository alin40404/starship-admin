<?php

namespace Starship\Core\Console\Commands;

use InvalidArgumentException;
use Starship\Core\Plugin\PluginLifecycle;
use Starship\Core\Plugin\PluginManager;

class PluginUninstallCommand extends PluginActionCommand
{
    protected $signature = 'starship:plugin:uninstall {plugin : 插件目录名或 slug}';

    protected $description = '卸载本地插件（须先禁用）';

    public function handle(PluginManager $pluginManager, PluginLifecycle $pluginLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($pluginManager);
            $pluginLifecycle->uninstall($slug);
            $this->info("插件 {$slug} 已卸载");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
