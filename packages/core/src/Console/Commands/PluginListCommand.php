<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Starship\Core\Plugin\Plugin;
use Starship\Core\Plugin\PluginManager;

class PluginListCommand extends Command
{
    protected $signature = 'starship:plugin:list';

    protected $description = '列出所有插件及状态';

    public function handle(PluginManager $pluginManager): int
    {
        $plugins = $pluginManager->allFromFilesystem();

        if ($plugins->isEmpty()) {
            $this->warn('未发现任何插件');

            return self::SUCCESS;
        }

        $rows = $plugins->map(static fn (Plugin $plugin) => [
            $plugin->slug(),
            $plugin->name(),
            $plugin->version(),
            $plugin->statusLabel(),
            $plugin->directoryName(),
        ])->all();

        $this->table(['Slug', 'Name', 'Version', 'Status', 'Directory'], $rows);

        return self::SUCCESS;
    }
}
