<?php

namespace Starship\Core\Console\Commands;

use InvalidArgumentException;
use Starship\Core\Plugin\PluginLifecycle;
use Starship\Core\Plugin\PluginManager;

class PluginEnableCommand extends PluginActionCommand
{
    protected $signature = 'starship:plugin:enable {plugin : 插件目录名或 slug}';

    protected $description = '启用本地插件（未安装时会自动安装）';

    public function handle(PluginManager $pluginManager, PluginLifecycle $pluginLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($pluginManager);
            $pluginLifecycle->enable($slug);
            $this->info("插件 {$slug} 已启用");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
