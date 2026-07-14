<?php

namespace Starship\Core\Module;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleLocator
{
    public function __construct(
        protected string $modulesPath
    ) {}

    /**
     * @return array{directory: string, data: array<string, mixed>}|null
     */
    public function find(string $nameOrSlug): ?array
    {
        $needle = Str::lower(trim($nameOrSlug));
        if ($needle === '' || ! File::isDirectory($this->modulesPath)) {
            return null;
        }

        foreach (File::directories($this->modulesPath) as $directory) {
            $manifestPath = $directory.DIRECTORY_SEPARATOR.'module.json';
            $data = ModuleManifest::load($manifestPath);
            if ($data === null) {
                continue;
            }

            $slug = Str::lower((string) ($data['name'] ?? ''));
            $basename = Str::lower(basename($directory));
            $studly = Str::lower(Str::studly($slug));

            if ($needle === $slug || $needle === $basename || $needle === $studly) {
                return [
                    'directory' => $directory,
                    'data' => $data,
                ];
            }
        }

        return null;
    }

    public function frontendFeatureRoot(string $slug): string
    {
        return $this->adminFrontendSrcRoot()
            .DIRECTORY_SEPARATOR.'features'
            .DIRECTORY_SEPARATOR.Str::lower($slug);
    }

    public function adminFrontendSrcRoot(): string
    {
        $configured = config('starship.admin_frontend_src');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return $this->monorepoRoot()
            .DIRECTORY_SEPARATOR.'apps'
            .DIRECTORY_SEPARATOR.'admin'
            .DIRECTORY_SEPARATOR.'web-antd'
            .DIRECTORY_SEPARATOR.'src';
    }

    public function monorepoRoot(): string
    {
        $configured = config('starship.repo_root');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return dirname(base_path());
    }
}
