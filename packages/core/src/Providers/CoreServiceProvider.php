<?php

namespace Starship\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Starship\Core\Console\Commands\PluginDisableCommand;
use Starship\Core\Console\Commands\PluginDiscoverCommand;
use Starship\Core\Console\Commands\PluginEnableCommand;
use Starship\Core\Console\Commands\PluginInstallCommand;
use Starship\Core\Console\Commands\PluginListCommand;
use Starship\Core\Module\ModuleManager;
use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginLifecycle;
use Starship\Core\Plugin\PluginLoader;
use Starship\Core\Plugin\PluginManager;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/starship.php', 'starship');

        $this->app->singleton(ModuleManager::class, function () {
            return new ModuleManager((string) config('starship.modules_path'));
        });

        $this->app->singleton(PluginManager::class, function () {
            return new PluginManager((string) config('starship.plugins_path'));
        });

        $this->app->singleton(PluginLoader::class);
        $this->app->singleton(PluginConfigRepository::class);
        $this->app->singleton(PluginLifecycle::class);

        $this->registerModules();
        $this->registerEnabledPlugins();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PluginDiscoverCommand::class,
                PluginListCommand::class,
                PluginInstallCommand::class,
                PluginEnableCommand::class,
                PluginDisableCommand::class,
            ]);
        }
    }

    protected function registerModules(): void
    {
        $manager = $this->app->make(ModuleManager::class);

        foreach ($manager->enabled() as $module) {
            foreach ($module->providers() as $provider) {
                if (class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }

    protected function registerEnabledPlugins(): void
    {
        try {
            $manager = $this->app->make(PluginManager::class);
            $loader = $this->app->make(PluginLoader::class);

            foreach ($manager->enabled() as $plugin) {
                $loader->register($plugin);
            }
        } catch (\Throwable) {
            // 数据库未就绪时（如 migrate）忽略
        }
    }
}
