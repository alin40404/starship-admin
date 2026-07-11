<?php

namespace Starship\Core\Plugin;

class Plugin
{
    public const STATUS_DISCOVERED = 0;

    public const STATUS_INSTALLED = 1;

    public const STATUS_ENABLED = 2;

    public const STATUS_DISABLED = 3;

    public function __construct(
        protected array $manifest,
        protected string $directory,
        protected ?array $record = null,
    ) {}

    public function name(): string
    {
        return (string) ($this->manifest['name'] ?? '');
    }

    public function slug(): string
    {
        return (string) ($this->manifest['slug'] ?? '');
    }

    public function title(): string
    {
        return $this->name();
    }

    public function type(): ?string
    {
        $type = $this->manifest['type'] ?? null;

        return is_string($type) && $type !== '' ? $type : null;
    }

    public function version(): string
    {
        return (string) ($this->manifest['version'] ?? '1.0.0');
    }

    public function description(): ?string
    {
        $description = $this->manifest['description'] ?? null;

        return is_string($description) && $description !== '' ? $description : null;
    }

    public function author(): ?string
    {
        $author = $this->manifest['author'] ?? null;

        return is_string($author) && $author !== '' ? $author : null;
    }

    public function namespace(): ?string
    {
        $namespace = $this->manifest['namespace'] ?? null;

        return is_string($namespace) && $namespace !== '' ? $namespace : null;
    }

    public function directoryName(): string
    {
        return basename(str_replace('\\', '/', $this->directory));
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function manifestPath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'plugin.json';
    }

    /**
     * @return list<string>
     */
    public function providers(): array
    {
        return $this->stringList($this->manifest['providers'] ?? []);
    }

    /**
     * @return list<string>
     */
    public function routes(): array
    {
        return $this->stringList($this->manifest['routes'] ?? []);
    }

    public function hasMigrations(): bool
    {
        return (bool) ($this->manifest['migrations'] ?? false);
    }

    public function configPath(): ?string
    {
        $config = $this->manifest['config'] ?? null;

        if (! is_string($config) || $config === '') {
            return null;
        }

        return $this->directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $config);
    }

    public function configKey(): string
    {
        return str_replace('-', '_', $this->slug());
    }

    /**
     * @return list<array{key: string, title: string, permission?: string}>
     */
    public function pages(): array
    {
        $pages = $this->manifest['pages'] ?? [];

        if (! is_array($pages)) {
            return [];
        }

        $result = [];
        foreach ($pages as $page) {
            if (! is_array($page)) {
                continue;
            }

            $key = $page['key'] ?? null;
            $title = $page['title'] ?? null;
            if (! is_string($key) || $key === '' || ! is_string($title) || $title === '') {
                continue;
            }

            $item = ['key' => $key, 'title' => $title];
            if (isset($page['permission']) && is_string($page['permission']) && $page['permission'] !== '') {
                $item['permission'] = $page['permission'];
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function require(): array
    {
        $require = $this->manifest['require'] ?? [];

        return is_array($require) ? $require : [];
    }

    public function status(): int
    {
        if ($this->record === null) {
            return self::STATUS_DISCOVERED;
        }

        return (int) ($this->record['status'] ?? self::STATUS_DISCOVERED);
    }

    public function isEnabled(): bool
    {
        return $this->status() === self::STATUS_ENABLED;
    }

    public function isInstalled(): bool
    {
        return in_array($this->status(), [self::STATUS_INSTALLED, self::STATUS_ENABLED, self::STATUS_DISABLED], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function manifest(): array
    {
        return $this->manifest;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function record(): ?array
    {
        return $this->record;
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            self::STATUS_INSTALLED => '已安装',
            self::STATUS_ENABLED => '已启用',
            self::STATUS_DISABLED => '已禁用',
            default => '未安装',
        };
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn ($item) => is_string($item) && $item !== '',
        ));
    }
}
