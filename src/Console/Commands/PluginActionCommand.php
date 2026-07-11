<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Starship\Core\Plugin\PluginManager;

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
