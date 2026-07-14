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
            $this->error("未找到模块：{$name}");

            return self::FAILURE;
        }

        $output = (string) ($this->option('output') ?: storage_path('app/starship-modules'));
        $asZip = ! (bool) $this->option('dir');

        try {
            $result = $builder->export($found, $output, $asZip);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $manifest = $result['manifest'];
        $this->info('模块已导出：'.($result['zip'] ?? $result['package_root']));
        $this->line('  name: '.$manifest['name']);
        $this->line('  version: '.$manifest['version']);
        $this->line('  backend_target: '.$manifest['backend_target']);
        $this->line('  admin_ui_target: '.$manifest['admin_ui_target']);
        $this->line('  has_admin_ui: '.(($manifest['has_admin_ui'] ?? false) ? 'yes' : 'no'));
        $this->newLine();
        $this->line('验收：见 docs/deployment/module-export-import-acceptance.md');

        return self::SUCCESS;
    }
}
