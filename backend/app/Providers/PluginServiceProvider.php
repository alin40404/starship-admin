<?php

namespace App\Providers;

use App\Foundation\Plugin\PluginConfigRepository;
use App\Foundation\Plugin\PluginLifecycle;
use App\Foundation\Plugin\PluginLoader;
use App\Foundation\Plugin\PluginManager;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class, static fn () => new PluginManager);
        $this->app->singleton(PluginLoader::class);
        $this->app->singleton(PluginConfigRepository::class);
        $this->app->singleton(PluginLifecycle::class);

        $manager = $this->app->make(PluginManager::class);
        $loader = $this->app->make(PluginLoader::class);

        try {
            foreach ($manager->enabled() as $plugin) {
                $loader->register($plugin);
            }
        } catch (\Throwable) {
            // 数据库未就绪时（如 migrate）忽略
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\PluginDiscoverCommand::class,
                \App\Console\Commands\PluginListCommand::class,
                \App\Console\Commands\PluginInstallCommand::class,
                \App\Console\Commands\PluginEnableCommand::class,
                \App\Console\Commands\PluginDisableCommand::class,
            ]);
        }
    }
}
