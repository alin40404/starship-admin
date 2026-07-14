<?php

namespace Starship\Core\Theme;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;

class ThemeAssetLinker
{
    public function __construct(
        protected ThemeManager $themes,
    ) {}

    /**
     * 将 public/themes/{slug}/assets 软链接到主题源 assets/，供 Web 服务器按静态文件访问。
     *
     * @return array{slug: string, source: string, target: string, linked: bool}
     */
    public function link(string $slug, bool $force = true): array
    {
        $theme = $this->themes->findBySlug($slug) ?? $this->themes->findByName($slug);
        if ($theme === null) {
            throw new InvalidArgumentException("主题不存在：{$slug}");
        }

        $source = $theme->assetsPath();
        if (! File::isDirectory($source)) {
            throw new InvalidArgumentException("主题无 assets 目录：{$theme->slug()}");
        }

        $sourceReal = realpath($source);
        if ($sourceReal === false) {
            throw new InvalidArgumentException("无法解析 assets 路径：{$source}");
        }

        $targetSlug = $theme->slug() !== '' ? $theme->slug() : $theme->directoryName();
        $target = public_path('themes'.DIRECTORY_SEPARATOR.$targetSlug.DIRECTORY_SEPARATOR.'assets');
        $parent = dirname($target);

        File::ensureDirectoryExists($parent);

        // 已指向正确源目录则直接成功，避免 Windows 无权限时「先删后建」把可用链接弄坏
        if ($this->pointsTo($target, $sourceReal)) {
            return [
                'slug' => $targetSlug,
                'source' => $sourceReal,
                'target' => $target,
                'linked' => true,
            ];
        }

        $this->removeExistingTarget($target, $force);
        $this->createLink($sourceReal, $target);

        return [
            'slug' => $targetSlug,
            'source' => $sourceReal,
            'target' => $target,
            'linked' => true,
        ];
    }

    /**
     * @return list<array{slug: string, source: string, target: string, linked: bool}>
     */
    public function linkAll(bool $force = true): array
    {
        $results = [];
        foreach ($this->themes->allFromFilesystem() as $theme) {
            if (! File::isDirectory($theme->assetsPath())) {
                continue;
            }
            $results[] = $this->link($theme->slug(), $force);
        }

        return $results;
    }

    protected function pointsTo(string $target, string $sourceReal): bool
    {
        if (! file_exists($target) && ! is_link($target)) {
            return false;
        }

        $resolved = realpath($target);
        if ($resolved === false) {
            return false;
        }

        return $this->samePath($resolved, $sourceReal);
    }

    protected function samePath(string $a, string $b): bool
    {
        $normalize = static function (string $path): string {
            $path = str_replace('\\', '/', $path);

            return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
        };

        return $normalize($a) === $normalize($b);
    }

    protected function createLink(string $sourceReal, string $target): void
    {
        if (@symlink($sourceReal, $target)) {
            return;
        }

        // Windows：目录联接一般不需要管理员 / 开发人员模式
        if (PHP_OS_FAMILY === 'Windows') {
            $command = sprintf(
                'cmd /c mklink /J %s %s',
                escapeshellarg($target),
                escapeshellarg($sourceReal),
            );
            $output = [];
            $code = 1;
            exec($command, $output, $code);
            if ($code === 0 && (is_link($target) || is_dir($target))) {
                return;
            }
        }

        throw new RuntimeException(
            "创建软链接失败：{$target} → {$sourceReal}。"
            .(PHP_OS_FAMILY === 'Windows'
                ? '请开启 Windows「开发人员模式」，或以管理员运行；或确认 mklink /J 可用。'
                : '请检查文件系统权限。'),
        );
    }

    protected function removeExistingTarget(string $target, bool $force): void
    {
        if (! file_exists($target) && ! is_link($target)) {
            return;
        }

        if (! $force) {
            throw new InvalidArgumentException("目标已存在：{$target}");
        }

        if (is_link($target)) {
            File::delete($target);

            return;
        }

        // Windows 目录联接：is_link 可能为 false，但属重解析点
        if (PHP_OS_FAMILY === 'Windows' && is_dir($target)) {
            $command = sprintf('cmd /c rmdir %s', escapeshellarg($target));
            exec($command, $output, $code);
            if ($code === 0 && ! file_exists($target)) {
                return;
            }
        }

        if (is_dir($target)) {
            File::deleteDirectory($target);
        } elseif (is_file($target)) {
            File::delete($target);
        }
    }
}
