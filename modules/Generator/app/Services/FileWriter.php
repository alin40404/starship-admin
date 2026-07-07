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
     * 向 routes/admin.php 的 system 路由组内写入带标记的路由片段。
     *
     * @param  list<string>  $useLines
     */
    public function appendRouteSnippet(
        string $routeFile,
        string $marker,
        string $snippet,
        array $useLines = [],
    ): array {
        if (! File::exists($routeFile)) {
            throw new \InvalidArgumentException("路由文件不存在：{$routeFile}");
        }

        $content = File::get($routeFile);
        foreach ($useLines as $useLine) {
            $content = $this->ensureUseImport($content, $useLine);
        }

        $start = "        // @generator:{$marker}:start";
        $end = "        // @generator:{$marker}:end";
        $snippet = str_replace("\r\n", "\n", $snippet);
        $snippet = (string) preg_replace("/\n{2,}/", "\n", rtrim($snippet));
        $block = $start."\n".$snippet."\n".$end;

        $controllerClass = $this->extractControllerClass($snippet);
        if ($controllerClass !== null) {
            $content = $this->removeStaleGeneratorBlocks($content, $controllerClass, $marker);
        }

        if (str_contains($content, $start)) {
            $pattern = '/'.preg_quote($start, '/').'.*?'.preg_quote($end, '/').'/s';
            $content = (string) preg_replace($pattern, $block, $content, 1);
        } elseif (str_contains($content, '// @generator:routes:anchor')) {
            $content = str_replace(
                '// @generator:routes:anchor',
                $block.PHP_EOL.PHP_EOL.'        // @generator:routes:anchor',
                $content,
            );
        } elseif (preg_match('/\r?\n    }\);\r?\n}\);\s*$/', $content)) {
            $content = (string) preg_replace(
                '/\r?\n    }\);\r?\n}\);\s*$/',
                "\n\n{$block}\n    });\n});",
                $content,
                1,
            );
        } else {
            throw new \InvalidArgumentException('未找到 system 路由组闭合位置，无法插入生成路由');
        }

        File::put($routeFile, $content);

        return ['path' => $routeFile, 'action' => 'updated'];
    }

    protected function extractControllerClass(string $snippet): ?string
    {
        if (preg_match('/\[(\w+Controller)::class/', $snippet, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * 移除同一 Controller 的旧生成块，避免 api_prefix 变更后重复插入。
     */
    protected function removeStaleGeneratorBlocks(string $content, string $controllerClass, string $currentMarker): string
    {
        $pattern = '/        \/\/ @generator:(?!'.preg_quote($currentMarker, '/').':)[^:]+:start.*?        \/\/ @generator:[^:]+:end\r?\n?/s';

        return (string) preg_replace_callback($pattern, function (array $matches) use ($controllerClass): string {
            return str_contains($matches[0], "[{$controllerClass}::class") ? '' : $matches[0];
        }, $content);
    }

    protected function ensureUseImport(string $content, string $useLine): string
    {
        if (str_contains($content, $useLine)) {
            return $content;
        }

        return (string) preg_replace(
            '/(use Illuminate\\\\Support\\\\Facades\\\\Route;)/',
            $useLine."\n$1",
            $content,
            1,
        );
    }
}
