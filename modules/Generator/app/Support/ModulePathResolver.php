<?php

namespace Modules\Generator\App\Support;

use App\Support\Module\ModuleManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ModulePathResolver
{
    public function __construct(
        protected ?string $modulesPath = null,
    ) {
        $this->modulesPath ??= base_path('../modules');
    }

    public static function make(): self
    {
        return new self();
    }

    /**
     * @return list<string>
     */
    public function availableModules(): array
    {
        return (new ModuleManager($this->modulesPath))
            ->enabled()
            ->map(fn ($module) => $module->name())
            ->values()
            ->all();
    }

    public function resolveModuleDirectory(string $moduleSlug): string
    {
        $slug = strtolower($moduleSlug);
        $manager = new ModuleManager($this->modulesPath);

        foreach ($manager->all() as $module) {
            if (strtolower($module->name()) === $slug) {
                $directory = $this->modulesPath.DIRECTORY_SEPARATOR.ucfirst($module->name());
                if (! File::isDirectory($directory)) {
                    $directory = $this->modulesPath.DIRECTORY_SEPARATOR.$module->name();
                }

                if (File::isDirectory($directory)) {
                    return $directory;
                }
            }
        }

        $candidates = [
            $this->modulesPath.DIRECTORY_SEPARATOR.$moduleSlug,
            $this->modulesPath.DIRECTORY_SEPARATOR.ucfirst($slug),
        ];

        foreach ($candidates as $directory) {
            if (File::isDirectory($directory)) {
                return $directory;
            }
        }

        throw new InvalidArgumentException("目标模块不存在或未启用：{$moduleSlug}");
    }

    public function moduleNamespace(string $moduleSlug): string
    {
        $directory = $this->resolveModuleDirectory($moduleSlug);
        $name = basename(str_replace('\\', '/', $directory));

        return 'Modules\\'.Str::studly($name).'\\App';
    }

    public function frontendRoot(): string
    {
        return base_path('../apps/admin/apps/web-antd/src');
    }
}
