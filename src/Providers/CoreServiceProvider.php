<?php

namespace Starship\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Starship\Core\Console\Commands\ModuleMakeCommand;
use Starship\Core\Console\Commands\PluginDisableCommand;
use Starship\Core\Console\Commands\PluginDiscoverCommand;
use Starship\Core\Console\Commands\PluginEnableCommand;
use Starship\Core\Console\Commands\PluginInstallCommand;
use Starship\Core\Console\Commands\PluginListCommand;
use Starship\Core\Console\Commands\PluginMakeCommand;
use Starship\Core\Console\Commands\PluginUninstallCommand;
use Starship\Core\Console\Commands\ThemeDisableCommand;
use Starship\Core\Console\Commands\ThemeDiscoverCommand;
use Starship\Core\Console\Commands\ThemeEnableCommand;
use Starship\Core\Console\Commands\ThemeInstallCommand;
use Starship\Core\Console\Commands\ThemeListCommand;
use Starship\Core\Console\Commands\ThemeSwitchCommand;
use Starship\Core\Console\Commands\ThemeUninstallCommand;
use Starship\Core\Module\ModuleManager;
use Starship\Core\Module\ModuleValidator;
use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginLifecycle;
use Starship\Core\Plugin\PluginLoader;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeManager;

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

        $this->app->singleton(ThemeManager::class, function () {
            return new ThemeManager((string) config('starship.themes_path'));
        });

        $this->app->singleton(PluginLoader::class);
        $this->app->singleton(PluginConfigRepository::class);
        $this->app->singleton(PluginLifecycle::class);
        $this->app->singleton(ThemeLifecycle::class);

        $this->registerModules();
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'core');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // 须在 System 等模块 boot 完成（admin.permission 中间件别名）后再加载插件路由
        $this->app->booted(function (): void {
            $this->registerEnabledPlugins();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleMakeCommand::class,
                PluginDiscoverCommand::class,
                PluginListCommand::class,
                PluginInstallCommand::class,
                PluginEnableCommand::class,
                PluginDisableCommand::class,
                PluginUninstallCommand::class,
                PluginMakeCommand::class,
                ThemeDiscoverCommand::class,
                ThemeListCommand::class,
                ThemeInstallCommand::class,
                ThemeEnableCommand::class,
                ThemeSwitchCommand::class,
                ThemeDisableCommand::class,
                ThemeUninstallCommand::class,
            ]);
        }
    }

    protected function registerModules(): void
    {
        $manager = $this->app->make(ModuleManager::class);
        $enabled = $manager->enabled();

        app(ModuleValidator::class)->assertAll($enabled);

        foreach ($enabled as $module) {
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
