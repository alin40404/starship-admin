<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Starship\Core\Module\ModuleLocator;
use Starship\Core\Module\ModulePackageBuilder;
use Throwable;

class ModuleImportCommand extends Command
{
    protected $signature = 'starship:module:import
                            {path : 模块包目录或 .zip 路径}
                            {--force : 覆盖已存在的 backend / admin-ui}
                            {--migrate : 导入后运行该模块 migrations}
                            {--seed : 导入后运行 manifest 中的 seeders}
                            {--no-admin-ui : 不导入前端 admin-ui}';

    protected $description = '导入业务模块包到 modules/ 与 web-antd/src/features/';

    public function handle(ModuleLocator $locator, ModulePackageBuilder $builder): int
    {
        $path = (string) $this->argument('path');
        $opened = null;

        try {
            $opened = $builder->openPackage($path);
            $builder->import(
                $opened['extracted_root'],
                $opened['manifest'],
                (bool) $this->option('force'),
                ! (bool) $this->option('no-admin-ui'),
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if (is_array($opened) && ($opened['cleanup'] ?? false) && isset($opened['staging'])) {
                File::deleteDirectory((string) $opened['staging']);
            }
        }

        $manifest = $opened['manifest'];
        $slug = Str::lower((string) ($manifest['name'] ?? ''));
        $studly = Str::studly($slug);
        $backendAbsolute = $locator->monorepoRoot()
            .DIRECTORY_SEPARATOR.str_replace(
                ['/', '\\'],
                DIRECTORY_SEPARATOR,
                (string) ($manifest['backend_target'] ?? ('modules/'.$studly)),
            );

        $this->info("模块 {$slug} 已导入到 {$backendAbsolute}");

        if ($this->option('migrate')) {
            $this->runModuleMigrations($backendAbsolute);
        }

        if ($this->option('seed')) {
            $this->runModuleSeeders($manifest);
        }

        $this->newLine();
        $this->line('建议下一步：');
        $this->line("  1. 确认 backend/composer.json 已 path 引用 starship/{$slug}，并执行 composer update starship/{$slug}");
        $this->line('  2. 确认 modules/'.$studly.'/module.json 中 enabled 为 true');
        if (! $this->option('migrate')) {
            $this->line('  3. php artisan migrate（或再次 import 加 --migrate）');
        }
        if (! $this->option('seed')) {
            $this->line('  4. 按需 db:seed 菜单 Seeder（或 --seed）');
        }
        $this->line('  5. 重启后端；前端执行 pnpm -F @vben/web-antd run dev / build');
        $this->line('验收清单：docs/deployment/module-export-import-acceptance.md');

        return self::SUCCESS;
    }

    protected function runModuleMigrations(string $backendAbsolute): void
    {
        $migrationPath = $backendAbsolute.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        if (! File::isDirectory($migrationPath)) {
            $this->warn('无 migrations 目录，跳过 --migrate');

            return;
        }

        $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $migrationPath);
        $this->info("运行迁移：{$migrationPath}");
        Artisan::call('migrate', [
            '--path' => $relative,
            '--force' => true,
        ]);
        $this->line(Artisan::output());
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function runModuleSeeders(array $manifest): void
    {
        $seeders = is_array($manifest['seeders'] ?? null) ? $manifest['seeders'] : [];
        foreach ($seeders as $seeder) {
            if (! is_string($seeder) || $seeder === '') {
                continue;
            }
            if (! class_exists($seeder)) {
                $this->warn("Seeder 类不存在（可能尚未 composer dump-autoload）：{$seeder}");
                continue;
            }
            $this->info("运行 Seeder：{$seeder}");
            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true,
            ]);
            $this->line(Artisan::output());
        }
    }
}
