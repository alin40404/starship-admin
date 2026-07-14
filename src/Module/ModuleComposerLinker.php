<?php

namespace Starship\Core\Module;

use InvalidArgumentException;
use RuntimeException;

/**
 * 将 modules/{Name} 挂到应用壳 backend/composer.json（path 仓库 + require @dev）。
 */
class ModuleComposerLinker
{
    /**
     * @return array{
     *     package: string,
     *     path_url: string,
     *     repository_added: bool,
     *     require_added: bool,
     *     changed: bool,
     * }
     */
    public function link(
        string $backendComposerJsonPath,
        string $moduleDirectory,
        string $packageConstraint = '@dev',
    ): array {
        $backendComposerJsonPath = $this->normalizePath($backendComposerJsonPath);
        $moduleDirectory = $this->normalizePath($moduleDirectory);

        if (! is_file($backendComposerJsonPath)) {
            throw new InvalidArgumentException("找不到 composer.json：{$backendComposerJsonPath}");
        }

        $moduleComposerPath = $moduleDirectory.DIRECTORY_SEPARATOR.'composer.json';
        if (! is_file($moduleComposerPath)) {
            throw new InvalidArgumentException("模块缺少 composer.json：{$moduleComposerPath}");
        }

        $moduleComposer = json_decode((string) file_get_contents($moduleComposerPath), true);
        if (! is_array($moduleComposer)) {
            throw new InvalidArgumentException("模块 composer.json 不是有效 JSON：{$moduleComposerPath}");
        }

        $package = (string) ($moduleComposer['name'] ?? '');
        if ($package === '' || ! str_contains($package, '/')) {
            throw new InvalidArgumentException('模块 composer.json 缺少有效的 name（如 starship/shop）');
        }

        $backendDir = dirname($backendComposerJsonPath);
        $resolvedBackend = realpath($backendDir) ?: $backendDir;
        $resolvedModule = realpath($moduleDirectory) ?: $moduleDirectory;
        $pathUrl = $this->relativeUrl($resolvedBackend, $resolvedModule);

        $composer = json_decode((string) file_get_contents($backendComposerJsonPath), true);
        if (! is_array($composer)) {
            throw new RuntimeException("应用 composer.json 不是有效 JSON：{$backendComposerJsonPath}");
        }

        $repositoryAdded = $this->ensurePathRepository($composer, $pathUrl);
        $requireAdded = $this->ensureRequire($composer, $package, $packageConstraint);
        $changed = $repositoryAdded || $requireAdded;

        if ($changed) {
            $encoded = json_encode(
                $composer,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
            if (file_put_contents($backendComposerJsonPath, $encoded.PHP_EOL) === false) {
                throw new RuntimeException("无法写入：{$backendComposerJsonPath}");
            }
        }

        return [
            'package' => $package,
            'path_url' => $pathUrl,
            'repository_added' => $repositoryAdded,
            'require_added' => $requireAdded,
            'changed' => $changed,
        ];
    }

    /**
     * @param  array<string, mixed>  $composer
     */
    protected function ensurePathRepository(array &$composer, string $pathUrl): bool
    {
        if (! isset($composer['repositories']) || ! is_array($composer['repositories'])) {
            $composer['repositories'] = [];
        }

        foreach ($composer['repositories'] as $repository) {
            if (! is_array($repository)) {
                continue;
            }
            $type = (string) ($repository['type'] ?? '');
            $url = $this->normalizeRepoUrl((string) ($repository['url'] ?? ''));
            if ($type === 'path' && $url === $this->normalizeRepoUrl($pathUrl)) {
                return false;
            }
        }

        $composer['repositories'][] = [
            'type' => 'path',
            'url' => $pathUrl,
        ];

        return true;
    }

    /**
     * @param  array<string, mixed>  $composer
     */
    protected function ensureRequire(array &$composer, string $package, string $constraint): bool
    {
        if (! isset($composer['require']) || ! is_array($composer['require'])) {
            $composer['require'] = [];
        }

        if (($composer['require'][$package] ?? null) === $constraint) {
            return false;
        }

        $composer['require'][$package] = $constraint;
        ksort($composer['require']);

        return true;
    }

    protected function relativeUrl(string $fromDirectory, string $toDirectory): string
    {
        $from = $this->pathSegments($fromDirectory);
        $to = $this->pathSegments($toDirectory);

        while ($from !== [] && $to !== [] && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        $segments = array_merge(array_fill(0, count($from), '..'), $to);
        if ($segments === []) {
            return '.';
        }

        return implode('/', $segments);
    }

    /**
     * @return list<string>
     */
    protected function pathSegments(string $path): array
    {
        $normalized = str_replace('\\', '/', $this->normalizePath($path));
        $normalized = rtrim($normalized, '/');

        if ($normalized === '') {
            return [];
        }

        // Windows drive letter stays as first segment (E:/work/...)
        return array_values(array_filter(explode('/', $normalized), static fn (string $p): bool => $p !== ''));
    }

    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    protected function normalizeRepoUrl(string $url): string
    {
        return rtrim(str_replace('\\', '/', $url), '/');
    }
}
