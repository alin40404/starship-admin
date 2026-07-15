<?php

namespace Starship\Core\Plugin;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * 导出产物：
 * - plugin/   ← 仓库 plugins/{Studly}（PHP + 可选 frontend/ 前台）
 * - backend/  ← 管理端 apps/admin/web-antd/src/plugins/{slug}（后台前端）
 */
class PluginPackageBuilder
{
    /** @var list<string> 不打进 plugin/ 的残留目录名 */
    private const PLUGIN_COPY_SKIP = ['backend'];

    public function __construct(
        protected PluginManager $pluginManager,
    ) {}

    /**
     * @return array{staging: string, package_root: string, manifest: array<string, mixed>, zip: ?string}
     */
    public function export(Plugin $plugin, string $outputDir, bool $asZip = true): array
    {
        $slug = $plugin->slug();
        if ($slug === '') {
            throw new InvalidArgumentException('plugin.json 缺少 slug');
        }

        $version = $plugin->version() !== '' ? $plugin->version() : '0.0.0';
        $packageName = $slug.'-'.$version;
        $studly = $plugin->directoryName();
        $pluginDir = $plugin->directory();
        $adminBackendDir = $this->adminBackendAbsolutePath($slug);
        $hasAdminBackend = $this->dirHasFiles($adminBackendDir);
        $hasFrontend = $this->dirHasFiles($pluginDir.DIRECTORY_SEPARATOR.'frontend');

        File::ensureDirectoryExists($outputDir);
        $staging = rtrim($outputDir, '/\\').DIRECTORY_SEPARATOR.'.starship-plugin-export-'.uniqid('', true);
        $packageRoot = $staging.DIRECTORY_SEPARATOR.$packageName;

        try {
            $pluginTarget = $packageRoot.DIRECTORY_SEPARATOR.'plugin';
            File::ensureDirectoryExists($pluginTarget);
            $this->copyPluginTree($pluginDir, $pluginTarget);

            $backendTarget = $packageRoot.DIRECTORY_SEPARATOR.'backend';
            File::ensureDirectoryExists($backendTarget);
            if ($hasAdminBackend) {
                File::copyDirectory($adminBackendDir, $backendTarget);
            }

            $manifest = $this->buildManifest($plugin, $studly, $slug, $hasAdminBackend, $hasFrontend);
            File::put(
                $packageRoot.DIRECTORY_SEPARATOR.'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
            );

            $this->assertExportLooksValid($packageRoot, $manifest);

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
            throw new InvalidArgumentException('仅支持目录或 .zip 插件包');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('需要 PHP ZipArchive 扩展才能导入 zip');
        }

        $staging = sys_get_temp_dir().DIRECTORY_SEPARATOR.'starship-plugin-import-'.uniqid('', true);
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
    public function import(string $packageRoot, array $manifest, bool $force = false, bool $withBackend = true): void
    {
        $slug = Str::lower((string) ($manifest['slug'] ?? $manifest['name'] ?? ''));
        if ($slug === '') {
            throw new InvalidArgumentException('manifest.json 缺少 slug');
        }

        $pluginSource = $this->resolvePluginSource($packageRoot);
        if ($pluginSource === null) {
            throw new InvalidArgumentException('插件包缺少 plugin/');
        }

        $pluginTarget = (string) ($manifest['plugin_target'] ?? ('plugins/'.Str::studly($slug)));
        $pluginAbsolute = $this->absoluteFromMonorepo($pluginTarget);

        if (File::isDirectory($pluginAbsolute) && ! $force) {
            throw new InvalidArgumentException("目标已存在：{$pluginAbsolute}，使用 --force 覆盖");
        }
        if (File::isDirectory($pluginAbsolute)) {
            File::deleteDirectory($pluginAbsolute);
        }
        File::ensureDirectoryExists(dirname($pluginAbsolute));
        File::copyDirectory($pluginSource, $pluginAbsolute);

        if ($withBackend) {
            $backendSource = $packageRoot.DIRECTORY_SEPARATOR.'backend';
            $backendTarget = (string) (
                $manifest['backend_target']
                ?? $manifest['admin_ui_target']
                ?? ('apps/admin/web-antd/src/plugins/'.$slug)
            );
            $backendAbsolute = $this->absoluteFromMonorepo($backendTarget);

            if (File::isDirectory($backendSource) && count(File::allFiles($backendSource)) > 0) {
                if (File::isDirectory($backendAbsolute) && ! $force) {
                    throw new InvalidArgumentException("后台前端目标已存在：{$backendAbsolute}，使用 --force 覆盖");
                }
                if (File::isDirectory($backendAbsolute)) {
                    File::deleteDirectory($backendAbsolute);
                }
                File::ensureDirectoryExists(dirname($backendAbsolute));
                File::copyDirectory($backendSource, $backendAbsolute);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function buildManifest(
        Plugin $plugin,
        string $studlyDir,
        string $slug,
        bool $hasBackend,
        bool $hasFrontend,
    ): array {
        $require = $plugin->require();

        return [
            'type' => 'plugin',
            'name' => $plugin->name() !== '' ? $plugin->name() : $studlyDir,
            'slug' => $slug,
            'studly_name' => $studlyDir,
            'version' => $plugin->version() !== '' ? $plugin->version() : '0.0.0',
            'plugin_target' => 'plugins/'.$studlyDir,
            'backend_target' => 'apps/admin/web-antd/src/plugins/'.$slug,
            'has_backend' => $hasBackend,
            'has_frontend' => $hasFrontend,
            'starship' => is_string($require['starship'] ?? null) ? $require['starship'] : '>=1.0.0',
            'require' => [
                'php' => is_string($require['php'] ?? null) ? $require['php'] : '>=8.3',
                'modules' => array_values(array_filter(
                    is_array($require['modules'] ?? null) ? $require['modules'] : [],
                    static fn ($m) => is_string($m) && $m !== '',
                )),
                'plugins' => array_values(array_filter(
                    is_array($require['plugins'] ?? null) ? $require['plugins'] : [],
                    static fn ($p) => is_string($p) && $p !== '',
                )),
            ],
        ];
    }

    public function adminBackendAbsolutePath(string $slug): string
    {
        $frontendSrc = (string) config('starship.admin_frontend_src');

        return rtrim($frontendSrc, '/\\').DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$slug;
    }

    protected function copyPluginTree(string $source, string $dest): void
    {
        File::ensureDirectoryExists($dest);

        foreach (File::directories($source) as $dir) {
            $name = basename($dir);
            if (in_array($name, self::PLUGIN_COPY_SKIP, true)) {
                continue;
            }
            File::copyDirectory($dir, $dest.DIRECTORY_SEPARATOR.$name);
        }

        foreach (File::files($source) as $file) {
            File::copy($file->getPathname(), $dest.DIRECTORY_SEPARATOR.$file->getFilename());
        }
    }

    protected function dirHasFiles(string $dir): bool
    {
        return File::isDirectory($dir) && count(File::allFiles($dir)) > 0;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function assertExportLooksValid(string $packageRoot, array $manifest): void
    {
        if (! File::isFile($packageRoot.DIRECTORY_SEPARATOR.'plugin'.DIRECTORY_SEPARATOR.'plugin.json')) {
            throw new RuntimeException('导出校验失败：plugin/plugin.json 缺失');
        }
        if (($manifest['has_backend'] ?? false) === true
            && ! File::isDirectory($packageRoot.DIRECTORY_SEPARATOR.'backend')) {
            throw new RuntimeException('导出校验失败：backend 缺失');
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

        throw new InvalidArgumentException('无法识别插件包根目录（需含 manifest.json）');
    }

    protected function resolvePluginSource(string $packageRoot): ?string
    {
        $candidate = $packageRoot.DIRECTORY_SEPARATOR.'plugin';
        if (File::isDirectory($candidate) && File::isFile($candidate.DIRECTORY_SEPARATOR.'plugin.json')) {
            return $candidate;
        }

        if (File::isFile($packageRoot.DIRECTORY_SEPARATOR.'plugin.json')) {
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
