<?php

namespace Starship\Core\Plugin;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;

class PluginLoader
{
    public function __construct(
        protected Application $app,
    ) {}

    public function register(Plugin $plugin): void
    {
        (new PluginAutoloader)->register($plugin);

        foreach ($plugin->providers() as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }

        $this->registerConfig($plugin);
        $this->registerRoutes($plugin);
        $this->registerMigrations($plugin);
    }

    protected function registerConfig(Plugin $plugin): void
    {
        $configPath = $plugin->configPath();
        if ($configPath === null || ! is_file($configPath)) {
            return;
        }

        $key = 'plugin.'.$plugin->configKey();
        $this->app->make('config')->set($key, require $configPath);
    }

    protected function registerRoutes(Plugin $plugin): void
    {
        foreach ($plugin->routes() as $routeFile) {
            $path = $plugin->directory().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $routeFile);
            if (is_file($path)) {
                require $path;
            }
        }
    }

    protected function registerMigrations(Plugin $plugin): void
    {
        if (! $plugin->hasMigrations()) {
            return;
        }

        $migrationPath = $plugin->directory().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        if (File::isDirectory($migrationPath)) {
            $this->app->afterResolving('migrator', static function ($migrator) use ($migrationPath): void {
                $migrator->path($migrationPath);
            });
        }
    }
}
