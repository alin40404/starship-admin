<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\File;

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

    /**
     * 向 routes/admin.php 追加带标记的路由片段。
     */
    public function appendRouteSnippet(string $routeFile, string $marker, string $snippet): array
    {
        if (! File::exists($routeFile)) {
            throw new \InvalidArgumentException("路由文件不存在：{$routeFile}");
        }

        $start = "// @generator:{$marker}:start";
        $end = "// @generator:{$marker}:end";
        $content = File::get($routeFile);

        if (str_contains($content, $start)) {
            $pattern = '/'.preg_quote($start, '/').'.*?'.preg_quote($end, '/').'/s';
            $next = preg_replace($pattern, trim($start.PHP_EOL.$snippet.PHP_EOL.$end), $content, 1);

            File::put($routeFile, $next ?? $content);

            return ['path' => $routeFile, 'action' => 'updated'];
        }

        $append = PHP_EOL.PHP_EOL.$start.PHP_EOL.trim($snippet).PHP_EOL.$end.PHP_EOL;
        File::append($routeFile, $append);

        return ['path' => $routeFile, 'action' => 'appended'];
    }
}
