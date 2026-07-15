<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Starship\Core\Plugin\PluginPackageBuilder;
use Throwable;

class PluginImportCommand extends Command
{
    protected $signature = 'starship:plugin:import
                            {path : 插件包目录或 .zip 路径}
                            {--force : 覆盖已存在目标}
                            {--no-backend : 不导入后台前端 backend/}';

    protected $description = '导入插件包到 plugins/ 与 web-antd/src/plugins/';

    public function handle(PluginPackageBuilder $builder): int
    {
        $path = (string) $this->argument('path');
        $opened = null;
        $withBackend = ! (bool) $this->option('no-backend');

        try {
            $opened = $builder->openPackage($path);
            $builder->import(
                $opened['extracted_root'],
                $opened['manifest'],
                (bool) $this->option('force'),
                $withBackend,
            );
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if (is_array($opened) && ($opened['cleanup'] ?? false) && isset($opened['staging'])) {
                File::deleteDirectory((string) $opened['staging']);
            }
        }

        $manifest = $opened['manifest'];
        $slug = Str::lower((string) ($manifest['slug'] ?? $manifest['name'] ?? ''));

        $this->components->info('插件已导入');
        $this->newLine();
        $this->components->twoColumnDetail('Slug', $slug);
        $this->components->twoColumnDetail('版本', (string) ($manifest['version'] ?? ''));
        $this->components->twoColumnDetail('PHP 目录', (string) ($manifest['plugin_target'] ?? ''));
        if ($withBackend) {
            $this->components->twoColumnDetail(
                '后台前端',
                (string) ($manifest['backend_target'] ?? ''),
            );
        }

        $this->newLine();
        $this->components->info('建议下一步');
        $this->components->bulletList([
            "php artisan starship:plugin:install {$slug}",
            "php artisan starship:plugin:enable {$slug}",
            '重启后端；前端执行 pnpm -F @vben/web-antd run dev / build',
        ]);

        return self::SUCCESS;
    }
}
