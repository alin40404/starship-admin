<?php

namespace Starship\Core\Module;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class ModulePackageBuilder
{
    public function __construct(
        protected ModuleLocator $locator
    ) {}

    /**
     * @param  array{directory: string, data: array<string, mixed>}  $found
     * @return array{staging: string, package_root: string, manifest: array<string, mixed>, zip: ?string}
     */
    public function export(array $found, string $outputDir, bool $asZip = true): array
    {
        $data = $found['data'];
        $backendDir = $found['directory'];
        $slug = Str::lower((string) ($data['name'] ?? ''));
        if ($slug === '') {
            throw new InvalidArgumentException('module.json 缺少 name');
        }

        $version = is_string($data['version'] ?? null) && $data['version'] !== ''
            ? $data['version']
            : '0.0.0';
        $packageName = $slug.'-'.$version;
        $studly = basename($backendDir);
        $featureDir = $this->locator->frontendFeatureRoot($slug);

        File::ensureDirectoryExists($outputDir);
        $staging = rtrim($outputDir, '/\\').DIRECTORY_SEPARATOR.'.starship-export-'.uniqid('', true);
        $packageRoot = $staging.DIRECTORY_SEPARATOR.$packageName;

        try {
            File::ensureDirectoryExists($packageRoot.DIRECTORY_SEPARATOR.'backend');
            File::copyDirectory($backendDir, $packageRoot.DIRECTORY_SEPARATOR.'backend');

            $adminUiTarget = $packageRoot.DIRECTORY_SEPARATOR.'admin-ui';
            if (File::isDirectory($featureDir)) {
                File::ensureDirectoryExists($adminUiTarget);
                File::copyDirectory($featureDir, $adminUiTarget);
            } else {
                File::ensureDirectoryExists($adminUiTarget);
            }

            $manifest = $this->buildManifest($data, $studly, $slug, File::isDirectory($featureDir));
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
     * @return array{manifest: array<string, mixed>, extracted_root: string, cleanup: bool}
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
            throw new InvalidArgumentException('仅支持目录或 .zip 模块包');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('需要 PHP ZipArchive 扩展才能导入 zip');
        }

        $staging = sys_get_temp_dir().DIRECTORY_SEPARATOR.'starship-import-'.uniqid('', true);
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
    public function import(string $packageRoot, array $manifest, bool $force = false, bool $withAdminUi = true): void
    {
        $slug = Str::lower((string) ($manifest['name'] ?? ''));
        if ($slug === '') {
            throw new InvalidArgumentException('manifest.json 缺少 name');
        }

        $backendSource = $packageRoot.DIRECTORY_SEPARATOR.'backend';
        if (! File::isDirectory($backendSource)) {
            throw new InvalidArgumentException('模块包缺少 backend/');
        }

        $backendTarget = (string) ($manifest['backend_target'] ?? ('modules/'.Str::studly($slug)));
        $backendAbsolute = $this->absoluteFromMonorepo($backendTarget);

        if (File::isDirectory($backendAbsolute) && ! $force) {
            throw new InvalidArgumentException("目标已存在：{$backendAbsolute}，使用 --force 覆盖");
        }
        if (File::isDirectory($backendAbsolute)) {
            File::deleteDirectory($backendAbsolute);
        }
        File::ensureDirectoryExists(dirname($backendAbsolute));
        File::copyDirectory($backendSource, $backendAbsolute);

        if ($withAdminUi) {
            $adminSource = $packageRoot.DIRECTORY_SEPARATOR.'admin-ui';
            $adminTarget = (string) ($manifest['admin_ui_target'] ?? ('apps/admin/web-antd/src/features/'.$slug));
            $adminAbsolute = $this->absoluteFromMonorepo($adminTarget);

            if (File::isDirectory($adminSource) && count(File::allFiles($adminSource)) > 0) {
                if (File::isDirectory($adminAbsolute) && ! $force) {
                    throw new InvalidArgumentException("前端目标已存在：{$adminAbsolute}，使用 --force 覆盖");
                }
                if (File::isDirectory($adminAbsolute)) {
                    File::deleteDirectory($adminAbsolute);
                }
                File::ensureDirectoryExists(dirname($adminAbsolute));
                File::copyDirectory($adminSource, $adminAbsolute);
            }
        }

        File::put(
            $backendAbsolute.DIRECTORY_SEPARATOR.'manifest.export.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
        );
    }

    /**
     * @param  array<string, mixed>  $moduleData
     * @return array<string, mixed>
     */
    public function buildManifest(array $moduleData, string $studlyDir, string $slug, bool $hasAdminUi): array
    {
        $require = is_array($moduleData['require'] ?? null) ? $moduleData['require'] : [];
        $menus = is_array($moduleData['menus'] ?? null) ? $moduleData['menus'] : [];
        $permissions = is_array($moduleData['permissions'] ?? null) ? $moduleData['permissions'] : [];

        return [
            'name' => $slug,
            'title' => $moduleData['title'] ?? $studlyDir,
            'version' => $moduleData['version'] ?? '0.0.0',
            'backend_target' => 'modules/'.$studlyDir,
            'admin_ui_target' => 'apps/admin/web-antd/src/features/'.$slug,
            'has_admin_ui' => $hasAdminUi,
            'require' => [
                'modules' => array_values(array_filter(
                    is_array($require['modules'] ?? null) ? $require['modules'] : [],
                    static fn ($m) => is_string($m) && $m !== '',
                )),
            ],
            'seeders' => array_values(array_filter(
                is_array($moduleData['seeders'] ?? null) ? $moduleData['seeders'] : [],
                static fn ($s) => is_string($s) && $s !== '',
            )),
            'menus' => [
                'paths' => array_values(array_filter(
                    is_array($menus['paths'] ?? null) ? $menus['paths'] : [],
                    static fn ($p) => is_string($p) && $p !== '',
                )),
            ],
            'permissions' => [
                'prefixes' => array_values(array_filter(
                    is_array($permissions['prefixes'] ?? null) ? $permissions['prefixes'] : [],
                    static fn ($p) => is_string($p) && $p !== '',
                )),
                'exact' => array_values(array_filter(
                    is_array($permissions['exact'] ?? null) ? $permissions['exact'] : [],
                    static fn ($p) => is_string($p) && $p !== '',
                )),
            ],
            'menu_component_hint' => '/'.$slug.'/index',
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function assertExportLooksValid(string $packageRoot, array $manifest): void
    {
        if (! File::isFile($packageRoot.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'module.json')) {
            throw new RuntimeException('导出校验失败：backend/module.json 缺失');
        }
        if (($manifest['has_admin_ui'] ?? false) === true
            && ! File::isDirectory($packageRoot.DIRECTORY_SEPARATOR.'admin-ui')) {
            throw new RuntimeException('导出校验失败：admin-ui 缺失');
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

        throw new InvalidArgumentException('无法识别模块包根目录（需含 manifest.json）');
    }

    protected function absoluteFromMonorepo(string $relative): string
    {
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relative, '/\\'));

        return $this->locator->monorepoRoot().DIRECTORY_SEPARATOR.$relative;
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
