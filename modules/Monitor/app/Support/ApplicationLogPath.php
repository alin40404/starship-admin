<?php

namespace Modules\Monitor\App\Support;

/**
 * 根据 logging 配置解析当前应用日志文件路径（供异常日志页提示文案使用）。
 */
final class ApplicationLogPath
{
    /**
     * 管理端展示用路径（相对项目 storage 目录，如 storage/logs/laravel-2026-07-12.log）。
     */
    public static function displayHint(): string
    {
        $paths = self::resolveDefaultChannelPaths();
        if ($paths !== []) {
            return implode('、', $paths);
        }

        $channel = (string) config('logging.default', 'stack');

        return (string) __('monitor::messages.log_channel_output', ['channel' => $channel]);
    }

    /**
     * @return list<string>
     */
    private static function resolveDefaultChannelPaths(): array
    {
        $default = (string) config('logging.default', 'stack');

        return self::resolveChannelPaths($default);
    }

    /**
     * @return list<string>
     */
    private static function resolveChannelPaths(string $channel): array
    {
        $channel = trim($channel);
        if ($channel === '') {
            return [];
        }

        /** @var array<string, mixed>|null $config */
        $config = config("logging.channels.{$channel}");
        if (! is_array($config)) {
            return [];
        }

        $driver = (string) ($config['driver'] ?? '');

        return match ($driver) {
            'stack' => self::resolveStackPaths($config),
            'single' => self::filterPaths([
                self::toRelativePath((string) ($config['path'] ?? '')),
            ]),
            'daily' => self::filterPaths([
                self::toRelativeDailyPath((string) ($config['path'] ?? '')),
            ]),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private static function resolveStackPaths(array $config): array
    {
        $paths = [];
        $channels = $config['channels'] ?? [];
        if (! is_array($channels)) {
            return [];
        }

        foreach ($channels as $name) {
            if (! is_string($name) && ! is_int($name)) {
                continue;
            }
            $paths = array_merge($paths, self::resolveChannelPaths(trim((string) $name)));
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param  list<string|null>  $paths
     * @return list<string>
     */
    private static function filterPaths(array $paths): array
    {
        return array_values(array_filter($paths, static fn (?string $path) => is_string($path) && $path !== ''));
    }

    private static function toRelativePath(string $absolutePath): string
    {
        $absolutePath = trim($absolutePath);
        if ($absolutePath === '') {
            return '';
        }

        $normalizedAbsolute = str_replace('\\', '/', $absolutePath);
        $storageRoot = str_replace('\\', '/', storage_path());
        if (str_starts_with($normalizedAbsolute, $storageRoot)) {
            return 'storage/'.ltrim(substr($normalizedAbsolute, strlen($storageRoot)), '/');
        }

        return $normalizedAbsolute;
    }

    private static function toRelativeDailyPath(string $absolutePath): string
    {
        $absolutePath = trim($absolutePath);
        if ($absolutePath === '') {
            return '';
        }

        $directory = dirname(str_replace('\\', '/', $absolutePath));
        $filename = basename($absolutePath);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $datedFilename = $basename.'-'.date('Y-m-d').'.log';

        return self::toRelativePath($directory.'/'.$datedFilename);
    }
}
