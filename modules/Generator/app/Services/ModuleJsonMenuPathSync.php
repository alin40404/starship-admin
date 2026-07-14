<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\File;
use Modules\Generator\App\Support\ModulePathResolver;

/**
 * 精确增删 module.json 的 menus.paths，不改动其它字段。
 */
class ModuleJsonMenuPathSync
{
    public function __construct(
        protected ModulePathResolver $modulePathResolver,
    ) {}

    /**
     * @return 'added'|'exists'|'skipped'
     */
    public function add(string $moduleSlug, ?string $routePath): string
    {
        $path = $this->normalizePath($routePath);
        if ($path === null) {
            return 'skipped';
        }

        $manifestPath = $this->manifestPath($moduleSlug);
        $manifest = $this->readManifest($manifestPath);
        $paths = $this->menusPaths($manifest);

        if (in_array($path, $paths, true)) {
            return 'exists';
        }

        $paths[] = $path;
        $manifest['menus'] = array_merge(
            is_array($manifest['menus'] ?? null) ? $manifest['menus'] : [],
            ['paths' => array_values($paths)],
        );
        $this->writeManifest($manifestPath, $manifest);

        return 'added';
    }

    /**
     * @return 'removed'|'missing'|'skipped'
     */
    public function remove(string $moduleSlug, ?string $routePath): string
    {
        $path = $this->normalizePath($routePath);
        if ($path === null) {
            return 'skipped';
        }

        $manifestPath = $this->manifestPath($moduleSlug);
        $manifest = $this->readManifest($manifestPath);
        $paths = $this->menusPaths($manifest);
        $filtered = array_values(array_filter(
            $paths,
            static fn (string $item): bool => $item !== $path,
        ));

        if (count($filtered) === count($paths)) {
            return 'missing';
        }

        $manifest['menus'] = array_merge(
            is_array($manifest['menus'] ?? null) ? $manifest['menus'] : [],
            ['paths' => $filtered],
        );
        $this->writeManifest($manifestPath, $manifest);

        return 'removed';
    }

    protected function manifestPath(string $moduleSlug): string
    {
        return $this->modulePathResolver->resolveModuleDirectory($moduleSlug)
            .DIRECTORY_SEPARATOR
            .'module.json';
    }

    /**
     * @return array<string, mixed>
     */
    protected function readManifest(string $manifestPath): array
    {
        if (! File::exists($manifestPath)) {
            throw new \InvalidArgumentException("module.json 不存在：{$manifestPath}");
        }

        $decoded = json_decode((string) File::get($manifestPath), true);
        if (! is_array($decoded)) {
            throw new \InvalidArgumentException("module.json 解析失败：{$manifestPath}");
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function writeManifest(string $manifestPath, array $manifest): void
    {
        $encoded = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        if ($encoded === false) {
            throw new \RuntimeException("module.json 编码失败：{$manifestPath}");
        }

        File::put($manifestPath, $encoded."\n");
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    protected function menusPaths(array $manifest): array
    {
        $menus = $manifest['menus'] ?? null;
        if (! is_array($menus)) {
            return [];
        }

        $paths = $menus['paths'] ?? [];
        if (! is_array($paths)) {
            return [];
        }

        $normalized = [];
        foreach ($paths as $item) {
            if (! is_string($item)) {
                continue;
            }
            $path = $this->normalizePath($item);
            if ($path !== null) {
                $normalized[] = $path;
            }
        }

        return array_values(array_unique($normalized));
    }

    protected function normalizePath(?string $routePath): ?string
    {
        if ($routePath === null) {
            return null;
        }

        $path = trim($routePath);
        if ($path === '') {
            return null;
        }

        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return rtrim($path, '/') ?: '/';
    }
}
