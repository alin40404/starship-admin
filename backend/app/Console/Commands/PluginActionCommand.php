<?php

namespace App\Console\Commands;

use App\Foundation\Plugin\PluginLifecycle;
use App\Foundation\Plugin\PluginManager;
use Illuminate\Console\Command;
use InvalidArgumentException;

abstract class PluginActionCommand extends Command
{
    protected function resolveSlug(PluginManager $pluginManager): string
    {
        $name = (string) $this->argument('plugin');
        $plugin = $pluginManager->findByName($name) ?? $pluginManager->findBySlug($name);

        if ($plugin === null) {
            throw new InvalidArgumentException("插件不存在：{$name}");
        }

        return $plugin->slug();
    }
}
