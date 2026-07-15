<?php

use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Support\Hook\Hook;
use Starship\Core\Theme\ThemeLoader;

if (! function_exists('plugin_config')) {
    function plugin_config(string $key, mixed $default = null): mixed
    {
        [$slug, $configKey] = array_pad(explode('.', $key, 2), 2, null);
        if ($slug === null || $configKey === null || $slug === '' || $configKey === '') {
            return $default;
        }

        return app(PluginConfigRepository::class)->get($slug, $configKey, $default);
    }
}

if (! function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10): void
    {
        Hook::addAction($hook, $callback, $priority);
    }
}

if (! function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
        Hook::doAction($hook, ...$args);
    }
}

if (! function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10): void
    {
        Hook::addFilter($hook, $callback, $priority);
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return Hook::applyFilters($hook, $value, ...$args);
    }
}

if (! function_exists('theme_asset')) {
    /**
     * 当前前台主题 assets/ 下的资源 URL（相对路径，如 css/app.css）。
     *
     * 路径：themes/{slug}/assets/{path}
     * - STARSHIP_ASSET_URL：Theme/Plugin 共用 CDN（优先）
     * - 否则 asset()：尊重 Laravel ASSET_URL
     * - 本机未上 CDN：软链到 public 或 ThemeAssetController 回落
     */
    function theme_asset(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'assets/')) {
            $path = substr($path, strlen('assets/'));
        }

        $slug = (string) config('starship.default_theme', 'default');
        $versionFile = null;

        try {
            $loader = app(ThemeLoader::class);
            if (! $loader->registered()) {
                $loader->register('frontend');
            }
            $theme = $loader->theme();
            if ($theme !== null) {
                $slug = $theme->slug() !== '' ? $theme->slug() : $theme->directoryName();
            }

            $publicFile = public_path('themes/'.$slug.'/assets/'.$path);
            if (is_file($publicFile)) {
                $versionFile = $publicFile;
            } else {
                $versionFile = $theme?->resolveAssetPath($path);
            }
        } catch (\Throwable) {
            $publicFile = public_path('themes/'.$slug.'/assets/'.$path);
            if (is_file($publicFile)) {
                $versionFile = $publicFile;
            }
        }

        $relative = 'themes/'.$slug.'/assets/'.$path;
        $cdnBase = rtrim((string) config('starship.asset_url', ''), '/');
        $url = $cdnBase !== ''
            ? $cdnBase.'/'.$relative
            : asset($relative);

        if (is_string($versionFile) && is_file($versionFile)) {
            $url .= (str_contains($url, '?') ? '&' : '?').'v='.filemtime($versionFile);
        }

        return $url;
    }
}

if (! function_exists('plugin_asset')) {
    /**
     * 指定插件 assets/ 下的资源 URL（相对路径，如 images/logo.svg）。
     *
     * 路径：plugins/{slug}/assets/{path}
     * - STARSHIP_ASSET_URL：Theme/Plugin 共用 CDN（优先）
     * - 否则 asset()：尊重 Laravel ASSET_URL
     * - 本机未上 CDN：软链到 public 或 PluginAssetController 回落
     */
    function plugin_asset(string $slug, string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'assets/')) {
            $path = substr($path, strlen('assets/'));
        }

        $slug = trim(str_replace('\\', '/', $slug), '/');
        $versionFile = null;

        try {
            $plugin = app(PluginManager::class)->findBySlug($slug)
                ?? app(PluginManager::class)->findByName($slug);
            if ($plugin !== null) {
                $slug = $plugin->slug() !== '' ? $plugin->slug() : $plugin->directoryName();
            }

            $publicFile = public_path('plugins/'.$slug.'/assets/'.$path);
            if (is_file($publicFile)) {
                $versionFile = $publicFile;
            } else {
                $versionFile = $plugin?->resolveAssetPath($path);
            }
        } catch (\Throwable) {
            $publicFile = public_path('plugins/'.$slug.'/assets/'.$path);
            if (is_file($publicFile)) {
                $versionFile = $publicFile;
            }
        }

        $relative = 'plugins/'.$slug.'/assets/'.$path;
        $cdnBase = rtrim((string) config('starship.asset_url', ''), '/');
        $url = $cdnBase !== ''
            ? $cdnBase.'/'.$relative
            : asset($relative);

        if (is_string($versionFile) && is_file($versionFile)) {
            $url .= (str_contains($url, '?') ? '&' : '?').'v='.filemtime($versionFile);
        }

        return $url;
    }
}
