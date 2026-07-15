<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Starship\Core\Theme\ThemePackageBuilder;
use Throwable;

class ThemeImportCommand extends Command
{
    protected $signature = 'starship:theme:import
                            {path : 主题包目录或 .zip 路径}
                            {--force : 覆盖已存在目标}';

    protected $description = '导入主题包到 themes/{slug}/';

    public function handle(ThemePackageBuilder $builder): int
    {
        $path = (string) $this->argument('path');
        $opened = null;

        try {
            $opened = $builder->openPackage($path);
            $builder->import(
                $opened['extracted_root'],
                $opened['manifest'],
                (bool) $this->option('force'),
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

        $this->components->info('主题已导入');
        $this->newLine();
        $this->components->twoColumnDetail('Slug', $slug);
        $this->components->twoColumnDetail('版本', (string) ($manifest['version'] ?? ''));
        $this->components->twoColumnDetail('目标目录', (string) ($manifest['theme_target'] ?? ''));

        $this->newLine();
        $this->components->info('建议下一步');
        $this->components->bulletList([
            "php artisan starship:theme:install {$slug}",
            "php artisan starship:theme:enable {$slug}",
            "php artisan starship:theme:link {$slug}",
        ]);

        return self::SUCCESS;
    }
}
