<?php

namespace App\Console\Commands;

use App\Foundation\Plugin\PluginLifecycle;
use App\Foundation\Plugin\PluginManager;
use InvalidArgumentException;

class PluginEnableCommand extends PluginActionCommand
{
    protected $signature = 'starship:plugin:enable {plugin : 插件目录名或 slug}';

    protected $description = '启用本地插件';

    public function handle(PluginManager $pluginManager, PluginLifecycle $pluginLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($pluginManager);
            $pluginLifecycle->enable($slug);
            $this->info("插件 {$slug} 已启用，请重启后端服务后生效");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
