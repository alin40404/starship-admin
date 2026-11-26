<?php

namespace Starship\Core\Install;

use Illuminate\Support\Facades\File;
use RuntimeException;

class EnvWriter
{
    public function __construct(protected ?string $basePath = null) {}

    public function ensureEnvFile(): string
    {
        $envPath = $this->path('.env');
        if (File::exists($envPath)) {
            return $envPath;
        }

        $example = $this->path('.env.example');
        if (! File::exists($example)) {
            throw new RuntimeException('缺少 .env 与 .env.example，无法初始化环境配置');
        }

        File::copy($example, $envPath);

        return $envPath;
    }

    protected function path(string $relative): string
    {
        $root = rtrim($this->basePath ?? base_path(), '\\/');

        return $root.DIRECTORY_SEPARATOR.ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative), '\\/');
    }

    /**
     * @param  array<string, scalar|null>  $values
     */
    public function setMany(array $values): void
    {
        $envPath = $this->ensureEnvFile();
        $content = File::get($envPath);

        foreach ($values as $key => $value) {
            $content = $this->replaceOrAppend($content, (string) $key, $this->stringify($value));
        }

        File::put($envPath, $content);
        // 同步到当前进程，避免 serve 同进程仍读旧环境变量
        $this->applyToProcess($values);
    }

    /**
     * @param  array<string, scalar|null>  $values
     */
    protected function applyToProcess(array $values): void
    {
        foreach ($values as $key => $value) {
            $name = (string) $key;
            if ($value === null) {
                putenv($name);
                unset($_ENV[$name], $_SERVER[$name]);

                continue;
            }

            $raw = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
            putenv($name.'='.$raw);
            $_ENV[$name] = $raw;
            $_SERVER[$name] = $raw;
        }
    }

    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    protected function replaceOrAppend(string $content, string $key, string $value): string
    {
        $line = $key.'='.$value;
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $content) === 1) {
            return (string) preg_replace($pattern, $line, $content, 1);
        }

        $content = rtrim($content, "\r\n").PHP_EOL;

        return $content.$line.PHP_EOL;
    }

    protected function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $string = (string) $value;
        if ($string === '') {
            return '';
        }

        if (preg_match('/\s|#|"|\'|=/', $string) === 1) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $string).'"';
        }

        return $string;
    }
}
