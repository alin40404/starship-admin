<?php

namespace Modules\System\App\Services;

use Starship\Core\Theme\Theme;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeManager;

class ThemeService
{
    public function __construct(
        protected ThemeManager $themeManager,
        protected ThemeLifecycle $themeLifecycle,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->themeManager->allFromFilesystem()
            ->map(fn (Theme $theme) => $this->formatTheme($theme))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function show(string $slug): array
    {
        $theme = $this->requireTheme($slug);

        return array_merge($this->formatTheme($theme), [
            'manifest' => $theme->manifest(),
            'manifest_path' => $this->relativeManifestPath($theme->manifestPath()),
            'entry' => $theme->entry(),
            'layout' => $theme->layoutEntry(),
        ]);
    }

    /**
     * @return array{discovered: int, created: int, updated: int}
     */
    public function discover(): array
    {
        return $this->themeLifecycle->discover();
    }

    /**
     * @return array<string, mixed>
     */
    public function install(string $slug): array
    {
        return $this->formatTheme($this->themeLifecycle->install($slug));
    }

    /**
     * @return array<string, mixed>
     */
    public function enable(string $slug): array
    {
        return $this->formatTheme($this->themeLifecycle->enable($slug));
    }

    /**
     * @return array<string, mixed>
     */
    public function disable(string $slug): array
    {
        return $this->formatTheme($this->themeLifecycle->disable($slug));
    }

    /**
     * @return array<string, mixed>
     */
    public function uninstall(string $slug): array
    {
        return $this->formatTheme($this->themeLifecycle->uninstall($slug));
    }

    /**
     * @return array<string, mixed>
     */
    public function switch(string $slug, ?string $type = null): array
    {
        return $this->formatTheme($this->themeLifecycle->switch($slug, $type));
    }

    protected function requireTheme(string $slug): Theme
    {
        $theme = $this->themeManager->findBySlug($slug);
        if ($theme === null) {
            throw new \InvalidArgumentException("主题不存在：{$slug}");
        }

        return $theme;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatTheme(Theme $theme): array
    {
        return [
            'name' => $theme->name(),
            'slug' => $theme->slug(),
            'title' => $theme->title(),
            'type' => $theme->type(),
            'version' => $theme->version(),
            'description' => $theme->description(),
            'author' => $theme->author(),
            'directory' => $theme->directoryName(),
            'status' => $theme->status(),
            'status_label' => $theme->statusLabel(),
            'enabled' => $theme->isEnabled(),
            'installed' => $theme->isInstalled(),
            'is_active' => $theme->isActive(),
            'layout' => $theme->layoutEntry(),
            'cover' => $theme->coverRelativePath(),
            'cover_url' => $theme->coverAbsolutePath() !== null
                ? '/dev/themes/'.$theme->slug().'/cover'
                : null,
            'installed_at' => $theme->record()['installed_at'] ?? null,
            'enabled_at' => $theme->record()['enabled_at'] ?? null,
        ];
    }

    public function coverAbsolutePath(string $slug): string
    {
        $theme = $this->requireTheme($slug);
        $path = $theme->coverAbsolutePath();
        if ($path === null) {
            throw new \InvalidArgumentException("主题封面不存在：{$slug}");
        }

        return $path;
    }

    protected function relativeManifestPath(string $manifestPath): string
    {
        $root = dirname(base_path());

        return str_replace('\\', '/', str_replace($root.DIRECTORY_SEPARATOR, '', $manifestPath));
    }
}
