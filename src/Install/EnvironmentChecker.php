<?php

namespace Starship\Core\Install;

class EnvironmentChecker
{
    public const PHP_MIN_VERSION = '8.3.0';

    /**
     * @return array{
     *   passed: bool,
     *   php: array{label: string, required: string, current: string, passed: bool},
     *   extensions: list<array{name: string, required: bool, loaded: bool, passed: bool}>,
     *   directories: list<array{path: string, writable: bool, passed: bool}>
     * }
     */
    public function check(): array
    {
        $phpPassed = version_compare(PHP_VERSION, self::PHP_MIN_VERSION, '>=');

        $extensions = [];
        foreach ($this->requiredExtensions() as $name) {
            $loaded = extension_loaded($name);
            $extensions[] = [
                'name' => $name,
                'required' => true,
                'loaded' => $loaded,
                'passed' => $loaded,
            ];
        }
        foreach ($this->optionalExtensions() as $name) {
            $loaded = extension_loaded($name);
            $extensions[] = [
                'name' => $name,
                'required' => false,
                'loaded' => $loaded,
                'passed' => true,
            ];
        }

        $directories = [];
        foreach ($this->writablePaths() as $path) {
            $writable = is_dir($path) ? is_writable($path) : is_writable(dirname($path));
            $directories[] = [
                'path' => $this->relativePath($path),
                'writable' => $writable,
                'passed' => $writable,
            ];
        }

        $requiredExtPassed = ! in_array(false, array_column(
            array_filter($extensions, static fn (array $item): bool => $item['required']),
            'passed'
        ), true);
        $dirsPassed = ! in_array(false, array_column($directories, 'passed'), true);

        return [
            'passed' => $phpPassed && $requiredExtPassed && $dirsPassed,
            'php' => [
                'label' => 'PHP',
                'required' => self::PHP_MIN_VERSION.'+',
                'current' => PHP_VERSION,
                'passed' => $phpPassed,
            ],
            'extensions' => $extensions,
            'directories' => $directories,
        ];
    }

    /**
     * @return list<string>
     */
    protected function requiredExtensions(): array
    {
        return [
            'pdo',
            'mbstring',
            'openssl',
            'tokenizer',
            'xml',
            'ctype',
            'json',
            'fileinfo',
        ];
    }

    /**
     * @return list<string>
     */
    protected function optionalExtensions(): array
    {
        return [
            'pdo_mysql',
            'pdo_sqlite',
            'curl',
            'redis',
        ];
    }

    /**
     * @return list<string>
     */
    protected function writablePaths(): array
    {
        return [
            storage_path(),
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];
    }

    protected function relativePath(string $absolute): string
    {
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';
        $normalized = str_replace('\\', '/', $absolute);
        if (str_starts_with($normalized, $base)) {
            return substr($normalized, strlen($base));
        }

        return $normalized;
    }
}
