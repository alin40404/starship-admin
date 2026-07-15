<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Plugin\PluginPackageBuilder;
use Throwable;

class PluginExportCommand extends Command
{
    protected $signature = 'starship:plugin:export
                            {plugin : 插件 slug 或目录名（如 mail-smtp / MailSmtp）}
                            {--output= : 输出目录（默认 storage/app/starship-plugins）}
                            {--dir : 仅输出目录，不打 zip}';

    protected $description = '导出插件包（plugin + 可选 backend 后台前端）';

    public function handle(PluginManager $pluginManager, PluginPackageBuilder $builder): int
    {
        $name = (string) $this->argument('plugin');
        $plugin = $pluginManager->findBySlug($name) ?? $pluginManager->findByName($name);
        if ($plugin === null) {
            $this->components->error("未找到插件：{$name}");

            return self::FAILURE;
        }

        $output = (string) ($this->option('output') ?: storage_path('app/starship-plugins'));
        $asZip = ! (bool) $this->option('dir');

        try {
            $result = $builder->export($plugin, $output, $asZip);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $manifest = $result['manifest'];
        $this->components->info('插件已导出');
        $this->line('  '.($result['zip'] ?? $result['package_root']));
        $this->newLine();
        $this->components->twoColumnDetail('Slug', (string) ($manifest['slug'] ?? ''));
        $this->components->twoColumnDetail('版本', (string) ($manifest['version'] ?? ''));
        $this->components->twoColumnDetail('PHP 目录', (string) ($manifest['plugin_target'] ?? ''));
        $this->components->twoColumnDetail('后台前端', (string) ($manifest['backend_target'] ?? ''));
        $this->components->twoColumnDetail(
            '含 backend/',
            ($manifest['has_backend'] ?? false) ? '是' : '否',
        );
        $this->components->twoColumnDetail(
            '含 frontend/',
            ($manifest['has_frontend'] ?? false) ? '是' : '否',
        );

        return self::SUCCESS;
    }
}
