<?php

namespace App\Foundation\Plugin;

use Composer\Autoload\ClassLoader;

class PluginAutoloader
{
    /**
     * @var array<string, ClassLoader>
     */
    private static array $loaders = [];

    public function register(Plugin $plugin): void
    {
        $slug = $plugin->slug();
        if ($slug === '' || isset(self::$loaders[$slug])) {
            return;
        }

        $namespace = $plugin->namespace();
        if ($namespace === null || $namespace === '') {
            return;
        }

        $srcPath = $plugin->directory().DIRECTORY_SEPARATOR.'src';
        if (! is_dir($srcPath)) {
            return;
        }

        $loader = new ClassLoader;
        $loader->addPsr4(rtrim($namespace, '\\').'\\', $srcPath);
        $loader->register(true);

        self::$loaders[$slug] = $loader;
    }
}
