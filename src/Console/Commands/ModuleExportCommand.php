<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Starship\Core\Module\ModuleLocator;
use Starship\Core\Module\ModulePackageBuilder;
use Throwable;

class ModuleExportCommand extends Command
{
    protected $signature = 'starship:module:export
                            {name : 模块 name/slug/目录名（如 member 或 Member）}
                            {--output= : 输出目录（默认 storage/app/starship-modules）}
                            {--dir : 仅输出目录，不打 zip}';

    protected $description = '导出业务模块包（backend + admin-ui + manifest）';

    public function handle(ModuleLocator $locator, ModulePackageBuilder $builder): int
    {
        $name = (string) $this->argument('name');
        $found = $locator->find($name);
        if ($found === null) {
            $this->components->error("未找到模块：{$name}");

            return self::FAILURE;
        }

        $output = (string) ($this->option('output') ?: storage_path('app/starship-modules'));
        $asZip = ! (bool) $this->option('dir');

        try {
            $result = $builder->export($found, $output, $asZip);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $manifest = $result['manifest'];
        $this->components->info('模块已导出');
        $this->line('  '.($result['zip'] ?? $result['package_root']));
        $this->newLine();
        $this->components->twoColumnDetail('名称', $manifest['name']);
        $this->components->twoColumnDetail('版本', (string) $manifest['version']);
        $this->components->twoColumnDetail('后端目录', $manifest['backend_target']);
        $this->components->twoColumnDetail('前端目录', $manifest['admin_ui_target']);
        $this->components->twoColumnDetail(
            '含管理端 UI',
            ($manifest['has_admin_ui'] ?? false) ? '是' : '否',
        );

        return self::SUCCESS;
    }
}
