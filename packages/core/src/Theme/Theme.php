<?php

namespace Starship\Core\Theme;

class Theme
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
        $title = $this->manifest['title'] ?? null;

        return is_string($title) && $title !== '' ? $title : $this->name();
    }

    public function type(): string
    {
        $type = $this->manifest['type'] ?? 'frontend';

        return is_string($type) && $type !== '' ? $type : 'frontend';
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
        return $this->directory.DIRECTORY_SEPARATOR.'theme.json';
    }

    /**
     * @return array<string, mixed>
     */
    public function require(): array
    {
        $require = $this->manifest['require'] ?? [];

        return is_array($require) ? $require : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function entry(): array
    {
        $entry = $this->manifest['entry'] ?? [];

        return is_array($entry) ? $entry : [];
    }

    public function layoutEntry(): ?string
    {
        $layout = $this->entry()['layout'] ?? null;

        return is_string($layout) && $layout !== '' ? $layout : null;
    }

    public function homeEntry(): ?string
    {
        $home = $this->entry()['home'] ?? 'pages.home';

        return is_string($home) && $home !== '' ? $home : 'pages.home';
    }

    /**
     * Blade 视图根目录：优先 `{theme}/views`，否则主题根目录。
     */
    public function viewsPath(): string
    {
        $withViews = $this->directory.DIRECTORY_SEPARATOR.'views';

        return is_dir($withViews) ? $withViews : $this->directory;
    }

    /**
     * 前台静态资源根目录：`{theme}/assets`。
     */
    public function assetsPath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'assets';
    }

    /**
     * 解析 assets/ 下相对路径为绝对文件路径；越界或不存在返回 null。
     */
    public function resolveAssetPath(string $relative): ?string
    {
        $relative = str_replace('\\', '/', $relative);
        $relative = ltrim($relative, '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $assetsRoot = realpath($this->assetsPath());
        if ($assetsRoot === false || ! is_dir($assetsRoot)) {
            return null;
        }

        $candidate = $assetsRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $real = realpath($candidate);
        if ($real === false || ! is_file($real)) {
            return null;
        }

        $rootPrefix = $assetsRoot.DIRECTORY_SEPARATOR;
        if ($real !== $assetsRoot && ! str_starts_with($real, $rootPrefix)) {
            return null;
        }

        return $real;
    }

    /**
     * 封面相对路径（相对主题根目录），优先 theme.json 的 cover，其次常见文件名。
     */
    public function coverRelativePath(): ?string
    {
        $cover = $this->manifest['cover'] ?? null;
        if (is_string($cover) && $cover !== '') {
            $normalized = str_replace(['\\', '..'], ['/', ''], $cover);
            $absolute = $this->directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalized);
            if (is_file($absolute)) {
                return $normalized;
            }
        }

        foreach (['cover.jpg', 'cover.jpeg', 'cover.png', 'cover.webp', 'cover.svg', 'assets/cover.jpg', 'assets/cover.png', 'assets/cover.svg'] as $candidate) {
            $absolute = $this->directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (is_file($absolute)) {
                return $candidate;
            }
        }

        return null;
    }

    public function coverAbsolutePath(): ?string
    {
        $relative = $this->coverRelativePath();
        if ($relative === null) {
            return null;
        }

        $absolute = $this->directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

        return is_file($absolute) ? $absolute : null;
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

    public function isActive(): bool
    {
        if ($this->record === null) {
            return false;
        }

        return (int) ($this->record['is_active'] ?? 0) === 1;
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
        $label = match ($this->status()) {
            self::STATUS_INSTALLED => '已安装',
            self::STATUS_ENABLED => '已启用',
            self::STATUS_DISABLED => '已禁用',
            default => '未安装',
        };

        if ($this->isActive()) {
            return $label.' / 当前';
        }

        return $label;
    }
}
