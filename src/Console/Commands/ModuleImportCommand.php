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
            $this->components->error($e->getMessage());

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

        $this->components->info('模块已导入');
        $this->line('  '.$backendAbsolute);
        $this->newLine();
        $this->components->twoColumnDetail('名称', $slug);
        $this->components->twoColumnDetail('版本', (string) ($manifest['version'] ?? ''));
        $this->components->twoColumnDetail('后端目录', (string) ($manifest['backend_target'] ?? ('modules/'.$studly)));
        if (! (bool) $this->option('no-admin-ui')) {
            $this->components->twoColumnDetail(
                '前端目录',
                (string) ($manifest['admin_ui_target'] ?? ''),
            );
        }

        if ($this->option('migrate')) {
            $this->runModuleMigrations($backendAbsolute);
        }

        if ($this->option('seed')) {
            $this->runModuleSeeders($manifest);
        }

        $next = [
            "确认 backend/composer.json 已 path 引用 starship/{$slug}，并执行 composer update starship/{$slug}",
            '确认 modules/'.$studly.'/module.json 中 enabled 为 true',
        ];
        if (! $this->option('migrate')) {
            $next[] = 'php artisan migrate（或再次 import 加 --migrate）';
        }
        if (! $this->option('seed')) {
            $next[] = '按需 db:seed 菜单 Seeder（或 --seed）';
        }
        $next[] = '重启后端；前端执行 pnpm -F @vben/web-antd run dev / build';

        $this->newLine();
        $this->components->info('建议下一步');
        $this->components->bulletList($next);

        return self::SUCCESS;
    }

    protected function runModuleMigrations(string $backendAbsolute): void
    {
        $migrationPath = $backendAbsolute.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        if (! File::isDirectory($migrationPath)) {
            $this->components->warn('无 migrations 目录，跳过 --migrate');

            return;
        }

        $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $migrationPath);
        $this->newLine();
        $this->components->info('运行迁移');
        $this->line('  '.$migrationPath);
        Artisan::call('migrate', [
            '--path' => $relative,
            '--force' => true,
        ]);
        $output = trim(Artisan::output());
        if ($output !== '') {
            $this->line($output);
        }
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
                $this->components->warn("Seeder 类不存在（可能尚未 composer dump-autoload）：{$seeder}");
                continue;
            }
            $this->newLine();
            $this->components->info('运行 Seeder');
            $this->line('  '.$seeder);
            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true,
            ]);
            $output = trim(Artisan::output());
            if ($output !== '') {
                $this->line($output);
            }
        }
    }
}
