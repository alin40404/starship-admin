<?php

use App\Foundation\Plugin\PluginConfigRepository;

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
