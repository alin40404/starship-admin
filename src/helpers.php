<?php

use Starship\Core\Plugin\PluginConfigRepository;
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

if (! function_exists('theme_asset')) {
    /**
     * 当前前台主题 assets/ 下的资源 URL（相对路径，如 css/app.css）。
     *
     * 路径：themes/{slug}/assets/{path}
     * - STARSHIP_THEME_ASSET_URL：专用 CDN / 静态域名（优先）
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
        $cdnBase = rtrim((string) config('starship.theme_asset_url', ''), '/');
        $url = $cdnBase !== ''
            ? $cdnBase.'/'.$relative
            : asset($relative);

        if (is_string($versionFile) && is_file($versionFile)) {
            $url .= (str_contains($url, '?') ? '&' : '?').'v='.filemtime($versionFile);
        }

        return $url;
    }
}
