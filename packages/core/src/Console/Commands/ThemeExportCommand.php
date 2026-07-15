<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Starship\Core\Theme\ThemeManager;
use Starship\Core\Theme\ThemePackageBuilder;
use Throwable;

class ThemeExportCommand extends Command
{
    protected $signature = 'starship:theme:export
                            {theme : 主题 slug 或目录名（如 default / corporate）}
                            {--output= : 输出目录（默认 storage/app/starship-themes）}
                            {--dir : 仅输出目录，不打 zip}';

    protected $description = '导出主题包（themes/{slug} → theme/）';

    public function handle(ThemeManager $themeManager, ThemePackageBuilder $builder): int
    {
        $name = (string) $this->argument('theme');
        $theme = $themeManager->findBySlug($name) ?? $themeManager->findByName($name);
        if ($theme === null) {
            $this->components->error("未找到主题：{$name}");

            return self::FAILURE;
        }

        $output = (string) ($this->option('output') ?: storage_path('app/starship-themes'));
        $asZip = ! (bool) $this->option('dir');

        try {
            $result = $builder->export($theme, $output, $asZip);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $manifest = $result['manifest'];
        $this->components->info('主题已导出');
        $this->line('  '.($result['zip'] ?? $result['package_root']));
        $this->newLine();
        $this->components->twoColumnDetail('Slug', (string) ($manifest['slug'] ?? ''));
        $this->components->twoColumnDetail('版本', (string) ($manifest['version'] ?? ''));
        $this->components->twoColumnDetail('目标目录', (string) ($manifest['theme_target'] ?? ''));

        return self::SUCCESS;
    }
}
