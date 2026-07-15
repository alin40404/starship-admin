<?php

namespace Starship\Core\Theme;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * 导出产物：
 * - theme/  ← 仓库 themes/{slug}/（Blade + assets + lang）
 */
class ThemePackageBuilder
{
    public function __construct(
        protected ThemeManager $themeManager,
    ) {}

    /**
     * @return array{staging: string, package_root: string, manifest: array<string, mixed>, zip: ?string}
     */
    public function export(Theme $theme, string $outputDir, bool $asZip = true): array
    {
        $slug = $theme->slug();
        if ($slug === '') {
            throw new InvalidArgumentException('theme.json 缺少 slug');
        }

        $version = $theme->version() !== '' ? $theme->version() : '0.0.0';
        $packageName = $slug.'-'.$version;
        $themeDir = $theme->directory();

        File::ensureDirectoryExists($outputDir);
        $staging = rtrim($outputDir, '/\\').DIRECTORY_SEPARATOR.'.starship-theme-export-'.uniqid('', true);
        $packageRoot = $staging.DIRECTORY_SEPARATOR.$packageName;

        try {
            $themeTarget = $packageRoot.DIRECTORY_SEPARATOR.'theme';
            File::ensureDirectoryExists($themeTarget);
            File::copyDirectory($themeDir, $themeTarget);

            $manifest = $this->buildManifest($theme);
            File::put(
                $packageRoot.DIRECTORY_SEPARATOR.'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
            );

            $this->assertExportLooksValid($packageRoot);

            $zipPath = null;
            $finalDir = rtrim($outputDir, '/\\').DIRECTORY_SEPARATOR.$packageName;

            if ($asZip && class_exists(ZipArchive::class)) {
                $zipPath = rtrim($outputDir, '/\\').DIRECTORY_SEPARATOR.$packageName.'.zip';
                $this->zipDirectory($packageRoot, $zipPath, $packageName);
                File::deleteDirectory($staging);

                return [
                    'staging' => '',
                    'package_root' => $zipPath,
                    'manifest' => $manifest,
                    'zip' => $zipPath,
                ];
            }

            if (File::isDirectory($finalDir)) {
                File::deleteDirectory($finalDir);
            }
            File::move($packageRoot, $finalDir);
            File::deleteDirectory($staging);

            return [
                'staging' => '',
                'package_root' => $finalDir,
                'manifest' => $manifest,
                'zip' => null,
            ];
        } catch (\Throwable $e) {
            if (File::isDirectory($staging)) {
                File::deleteDirectory($staging);
            }
            throw $e;
        }
    }

    /**
     * @return array{manifest: array<string, mixed>, extracted_root: string, cleanup: bool, staging: string}
     */
    public function openPackage(string $path): array
    {
        $path = $this->normalizePath($path);
        if (! file_exists($path)) {
            throw new InvalidArgumentException("路径不存在：{$path}");
        }

        if (is_dir($path)) {
            $root = $this->resolvePackageRoot($path);

            return [
                'manifest' => $this->readManifest($root),
                'extracted_root' => $root,
                'cleanup' => false,
                'staging' => '',
            ];
        }

        if (! str_ends_with(Str::lower($path), '.zip')) {
            throw new InvalidArgumentException('仅支持目录或 .zip 主题包');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('需要 PHP ZipArchive 扩展才能导入 zip');
        }

        $staging = sys_get_temp_dir().DIRECTORY_SEPARATOR.'starship-theme-import-'.uniqid('', true);
        File::ensureDirectoryExists($staging);

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            File::deleteDirectory($staging);
            throw new InvalidArgumentException("无法打开 zip：{$path}");
        }
        $zip->extractTo($staging);
        $zip->close();

        $root = $this->resolvePackageRoot($staging);

        return [
            'manifest' => $this->readManifest($root),
            'extracted_root' => $root,
            'cleanup' => true,
            'staging' => $staging,
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function import(string $packageRoot, array $manifest, bool $force = false): void
    {
        $slug = Str::lower((string) ($manifest['slug'] ?? $manifest['name'] ?? ''));
        if ($slug === '') {
            throw new InvalidArgumentException('manifest.json 缺少 slug');
        }

        $themeSource = $this->resolveThemeSource($packageRoot);
        if ($themeSource === null) {
            throw new InvalidArgumentException('主题包缺少 theme/（或根目录 theme.json）');
        }

        $themeTarget = (string) ($manifest['theme_target'] ?? ('themes/'.$slug));
        $themeAbsolute = $this->absoluteFromMonorepo($themeTarget);

        if (File::isDirectory($themeAbsolute) && ! $force) {
            throw new InvalidArgumentException("目标已存在：{$themeAbsolute}，使用 --force 覆盖");
        }
        if (File::isDirectory($themeAbsolute)) {
            File::deleteDirectory($themeAbsolute);
        }
        File::ensureDirectoryExists(dirname($themeAbsolute));
        File::copyDirectory($themeSource, $themeAbsolute);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildManifest(Theme $theme): array
    {
        $slug = $theme->slug();
        $require = $theme->require();
        $dirName = $theme->directoryName();

        return [
            'type' => 'theme',
            'name' => $theme->name() !== '' ? $theme->name() : $dirName,
            'slug' => $slug,
            'title' => $theme->title(),
            'version' => $theme->version() !== '' ? $theme->version() : '0.0.0',
            'theme_type' => $theme->type(),
            'theme_target' => 'themes/'.($slug !== '' ? $slug : $dirName),
            'require' => [
                'modules' => array_values(array_filter(
                    is_array($require['modules'] ?? null) ? $require['modules'] : [],
                    static fn ($m) => is_string($m) && $m !== '',
                )),
            ],
        ];
    }

    protected function assertExportLooksValid(string $packageRoot): void
    {
        if (! File::isFile($packageRoot.DIRECTORY_SEPARATOR.'theme'.DIRECTORY_SEPARATOR.'theme.json')) {
            throw new RuntimeException('导出校验失败：theme/theme.json 缺失');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function readManifest(string $packageRoot): array
    {
        $path = $packageRoot.DIRECTORY_SEPARATOR.'manifest.json';
        if (! File::isFile($path)) {
            throw new InvalidArgumentException("找不到 manifest.json：{$path}");
        }

        $data = json_decode((string) File::get($path), true);
        if (! is_array($data)) {
            throw new InvalidArgumentException('manifest.json 无法解析');
        }

        if (($data['type'] ?? null) !== null && $data['type'] !== 'theme') {
            throw new InvalidArgumentException('manifest.type 不是 theme，请确认这是主题包');
        }

        return $data;
    }

    protected function resolvePackageRoot(string $path): string
    {
        if (File::isFile($path.DIRECTORY_SEPARATOR.'manifest.json')) {
            return $path;
        }

        $dirs = File::directories($path);
        if (count($dirs) === 1 && File::isFile($dirs[0].DIRECTORY_SEPARATOR.'manifest.json')) {
            return $dirs[0];
        }

        throw new InvalidArgumentException('无法识别主题包根目录（需含 manifest.json）');
    }

    protected function resolveThemeSource(string $packageRoot): ?string
    {
        $candidate = $packageRoot.DIRECTORY_SEPARATOR.'theme';
        if (File::isDirectory($candidate) && File::isFile($candidate.DIRECTORY_SEPARATOR.'theme.json')) {
            return $candidate;
        }

        if (File::isFile($packageRoot.DIRECTORY_SEPARATOR.'theme.json')) {
            return $packageRoot;
        }

        return null;
    }

    protected function absoluteFromMonorepo(string $relative): string
    {
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relative, '/\\'));
        $root = (string) config('starship.repo_root', dirname(base_path()));

        return rtrim($root, '/\\').DIRECTORY_SEPARATOR.$relative;
    }

    protected function zipDirectory(string $sourceDir, string $zipPath, string $rootNameInZip): void
    {
        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("无法创建 zip：{$zipPath}");
        }

        $sourceDir = realpath($sourceDir);
        if ($sourceDir === false) {
            $zip->close();
            throw new RuntimeException('导出源目录无效');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            $filePath = $file->getPathname();
            $relative = substr($filePath, strlen($sourceDir) + 1);
            $zipEntry = $rootNameInZip.'/'.str_replace('\\', '/', $relative);

            if ($file->isDir()) {
                $zip->addEmptyDir(rtrim($zipEntry, '/'));
            } else {
                $zip->addFile($filePath, $zipEntry);
            }
        }

        $zip->close();
    }

    protected function normalizePath(string $path): string
    {
        if (File::exists($path)) {
            return (string) realpath($path);
        }

        return $path;
    }
}
