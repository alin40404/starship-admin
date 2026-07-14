<?php

namespace Modules\Generator\App\Support;

use Starship\Core\Module\ModuleManager;
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

    /**
     * 将 CLI/UI 传入的模块名规范为 module.json 的 name（大小写不敏感）。
     */
    public function canonicalModuleSlug(string $moduleSlug): string
    {
        $normalized = strtolower(trim($moduleSlug));
        if ($normalized === '') {
            return $moduleSlug;
        }

        foreach ((new ModuleManager($this->modulesPath))->all() as $module) {
            if (strtolower($module->name()) === $normalized) {
                return $module->name();
            }
        }

        return trim($moduleSlug);
    }

    public function moduleExists(string $moduleSlug): bool
    {
        $normalized = strtolower(trim($moduleSlug));

        foreach ((new ModuleManager($this->modulesPath))->enabled() as $module) {
            if (strtolower($module->name()) === $normalized) {
                return true;
            }
        }

        return false;
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
        return base_path('../apps/admin/web-antd/src');
    }

    /**
     * 管理端按业务切分的前端 feature 根目录：web-antd/src/features/{slug}
     */
    public function frontendFeatureRoot(string $moduleSlug): string
    {
        return $this->frontendRoot().'/features/'.strtolower(
            $this->canonicalModuleSlug($moduleSlug),
        );
    }
}
