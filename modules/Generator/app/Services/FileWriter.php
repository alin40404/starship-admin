<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class FileWriter
{
    /**
     * @param  list<array{path: string, content: string}>  $files
     * @return array{
     *   written: list<array{path: string, action: string}>,
     *   skipped: list<array{path: string, reason: string}>,
     *   conflicts: list<array{path: string}>
     * }
     */
    public function writeMany(array $files, bool $force = false): array
    {
        $written = [];
        $skipped = [];
        $conflicts = [];

        foreach ($files as $file) {
            $path = $file['path'];
            $content = $file['content'];
            $exists = File::exists($path);

            if ($exists && ! $force) {
                $current = File::get($path);
                if (hash('sha256', $current) === hash('sha256', $content)) {
                    $skipped[] = ['path' => $path, 'reason' => 'unchanged'];
                } else {
                    $conflicts[] = ['path' => $path];
                    $skipped[] = ['path' => $path, 'reason' => 'exists'];
                }

                continue;
            }

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $content);
            $written[] = ['path' => $path, 'action' => $exists ? 'overwritten' : 'created'];
        }

        return compact('written', 'skipped', 'conflicts');
    }

    public function ensureAdminRouteLoader(string $moduleDir): void
    {
        $adminRoutesDir = $moduleDir.'/routes/admin-generator';
        $loaderFile = $adminRoutesDir.'/_loader.php';

        File::ensureDirectoryExists($adminRoutesDir);

        if (File::exists($loaderFile)) {
            return;
        }

        $loaderStub = dirname(__DIR__, 2).'/resources/stubs/backend/routes.loader.stub';
        if (! File::exists($loaderStub)) {
            throw new InvalidArgumentException("管理端路由 loader 模板不存在：{$loaderStub}");
        }

        File::copy($loaderStub, $loaderFile);
    }

    public function assertAdminRouteLoaderRegistered(string $adminRouteFile): void
    {
        if (! File::exists($adminRouteFile)) {
            throw new InvalidArgumentException("路由文件不存在：{$adminRouteFile}");
        }

        $content = (string) File::get($adminRouteFile);
        if (! str_contains($content, "admin-generator/_loader.php")) {
            throw new InvalidArgumentException(
                "模块管理端路由未注册 loader，请在 {$adminRouteFile} 的目标路由组内添加：require __DIR__.'/admin-generator/_loader.php';"
            );
        }
    }
}
