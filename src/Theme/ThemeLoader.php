<?php

namespace Starship\Core\Theme;

use Illuminate\Support\Facades\View;

/**
 * 将当前前台主题的 views 注册为 theme:: 命名空间，供 Blade 渲染。
 */
class ThemeLoader
{
    protected ?Theme $theme = null;

    protected bool $registered = false;

    public function __construct(
        protected ThemeManager $themes,
    ) {}

    /**
     * 解析并注册当前主题视图命名空间；失败时静默回退，不中断 Web / migrate。
     */
    public function register(string $type = 'frontend'): void
    {
        try {
            $theme = $this->resolveTheme($type);
        } catch (\Throwable) {
            $theme = null;
        }

        if ($theme === null) {
            return;
        }

        $viewsPath = $theme->viewsPath();
        if (! is_dir($viewsPath)) {
            return;
        }

        View::addNamespace('theme', $viewsPath);

        $langPath = $theme->directory().DIRECTORY_SEPARATOR.'lang';
        if (is_dir($langPath)) {
            app('translator')->addNamespace('theme', $langPath);
        }

        View::share('theme', [
            'name' => $theme->name(),
            'slug' => $theme->slug(),
            'title' => $theme->title(),
            'type' => $theme->type(),
            'directory' => $theme->directoryName(),
        ]);

        $this->theme = $theme;
        $this->registered = true;
    }

    public function registered(): bool
    {
        return $this->registered;
    }

    public function theme(): ?Theme
    {
        return $this->theme;
    }

    /**
     * 解析 entry 中的视图名为 theme::xxx（支持 layouts.app / layouts/app）。
     */
    public function view(string $entryKey = 'home'): string
    {
        $entry = $this->theme?->entry() ?? [];
        $raw = $entry[$entryKey] ?? null;

        if (! is_string($raw) || $raw === '') {
            $raw = match ($entryKey) {
                'layout' => 'layouts.app',
                'home' => 'pages.home',
                default => $entryKey,
            };
        }

        return 'theme::'.$this->normalizeViewName($raw);
    }

    protected function resolveTheme(string $type): ?Theme
    {
        $theme = $this->themes->current($type);
        if ($theme !== null && is_dir($theme->viewsPath())) {
            return $theme;
        }

        $fallbackSlug = (string) (config("starship.fallback_themes.{$type}")
            ?: config('starship.default_theme', 'default'));

        $fallback = $this->themes->findBySlug($fallbackSlug);
        if ($fallback !== null && $fallback->type() === $type && is_dir($fallback->viewsPath())) {
            return $fallback;
        }

        return $this->themes->allFromFilesystem()
            ->first(static fn (Theme $item) => $item->type() === $type && is_dir($item->viewsPath()));
    }

    protected function normalizeViewName(string $name): string
    {
        $name = str_replace(['\\', '/'], '.', $name);
        $name = (string) preg_replace('/\.blade\.php$/i', '', $name);
        $name = (string) preg_replace('/\.vue$/i', '', $name);

        return trim($name, '.');
    }
}
