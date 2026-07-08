<?php

namespace App\Console\Commands;

use App\Foundation\Plugin\PluginLifecycle;
use App\Foundation\Plugin\PluginManager;
use InvalidArgumentException;

class PluginDisableCommand extends PluginActionCommand
{
    protected $signature = 'starship:plugin:disable {plugin : 插件目录名或 slug}';

    protected $description = '禁用本地插件';

    public function handle(PluginManager $pluginManager, PluginLifecycle $pluginLifecycle): int
    {
        try {
            $slug = $this->resolveSlug($pluginManager);
            $pluginLifecycle->disable($slug);
            $this->info("插件 {$slug} 已禁用，请重启后端服务后生效");

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
