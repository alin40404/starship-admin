<?php

namespace Starship\Core\Providers;

use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Starship\Core\Console\Commands\InstallCommand;
use Starship\Core\Console\Commands\ModuleExportCommand;
use Starship\Core\Console\Commands\ModuleImportCommand;
use Starship\Core\Console\Commands\ModuleMakeCommand;
use Starship\Core\Console\Commands\PluginDisableCommand;
use Starship\Core\Console\Commands\PluginDiscoverCommand;
use Starship\Core\Console\Commands\PluginEnableCommand;
use Starship\Core\Console\Commands\PluginExportCommand;
use Starship\Core\Console\Commands\PluginImportCommand;
use Starship\Core\Console\Commands\PluginInstallCommand;
use Starship\Core\Console\Commands\PluginLinkCommand;
use Starship\Core\Console\Commands\PluginListCommand;
use Starship\Core\Console\Commands\PluginMakeCommand;
use Starship\Core\Console\Commands\PluginUninstallCommand;
use Starship\Core\Console\Commands\ThemeDeleteCommand;
use Starship\Core\Console\Commands\ThemeDisableCommand;
use Starship\Core\Console\Commands\ThemeDiscoverCommand;
use Starship\Core\Console\Commands\ThemeEnableCommand;
use Starship\Core\Console\Commands\ThemeExportCommand;
use Starship\Core\Console\Commands\ThemeImportCommand;
use Starship\Core\Console\Commands\ThemeInstallCommand;
use Starship\Core\Console\Commands\ThemeListCommand;
use Starship\Core\Console\Commands\ThemeLinkCommand;
use Starship\Core\Console\Commands\ThemeMakeCommand;
use Starship\Core\Console\Commands\ThemeSwitchCommand;
use Starship\Core\Console\Commands\ThemeUninstallCommand;
use Starship\Core\Event\EventDispatcher;
use Starship\Core\Event\EventDispatcherInterface;
use Starship\Core\Http\Middleware\EnsureInstalled;
use Starship\Core\Install\EnvironmentChecker;
use Starship\Core\Install\EnvWriter;
use Starship\Core\Install\InstallLock;
use Starship\Core\Install\InstallService;
use Starship\Core\Module\ModuleLocator;
use Starship\Core\Module\ModuleManager;
use Starship\Core\Module\ModulePackageBuilder;
use Starship\Core\Module\ModuleValidator;
use Starship\Core\Plugin\PluginAssetLinker;
use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginLifecycle;
use Starship\Core\Plugin\PluginLoader;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Plugin\PluginPackageBuilder;
use Starship\Core\Theme\ThemeAssetLinker;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeLoader;
use Starship\Core\Theme\ThemeManager;
use Starship\Core\Theme\ThemePackageBuilder;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/starship.php', 'starship');

        $this->app->singleton(EventDispatcherInterface::class, EventDispatcher::class);

        $this->app->singleton(ModuleManager::class, function () {
            return new ModuleManager((string) config('starship.modules_path'));
        });

        $this->app->singleton(ModuleLocator::class, function () {
            return new ModuleLocator((string) config('starship.modules_path'));
        });

        $this->app->singleton(ModulePackageBuilder::class, function ($app) {
            return new ModulePackageBuilder($app->make(ModuleLocator::class));
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
        $this->app->singleton(PluginAssetLinker::class);
        $this->app->singleton(PluginPackageBuilder::class);
        $this->app->singleton(ThemePackageBuilder::class);
        $this->app->singleton(ThemeLifecycle::class);
        $this->app->singleton(ThemeLoader::class);
        $this->app->singleton(ThemeAssetLinker::class);

        $this->app->singleton(InstallLock::class);
        $this->app->singleton(EnvironmentChecker::class);
        $this->app->singleton(EnvWriter::class);
        $this->app->singleton(InstallService::class);

        $this->registerModules();
    }

    public function boot(): void
    {
        // 生产环境禁止破坏性 migrate / db:seed
        DB::prohibitDestructiveCommands($this->app->isProduction());
        SeedCommand::prohibit($this->app->isProduction());

        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'core');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/install.php');

        // 路由别名；api 组挂载见应用 bootstrap/app.php（Laravel 11 appendToGroup）
        $this->app['router']->aliasMiddleware('admin.installed', EnsureInstalled::class);

        try {
            $this->app->make(ThemeLoader::class)->register('frontend');
        } catch (\Throwable) {
            // 数据库未就绪或主题目录缺失时忽略
        }

        // 须在 System 等模块 boot 完成（admin.permission 中间件别名）后再加载插件路由
        $this->app->booted(function (): void {
            $this->registerEnabledPlugins();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ModuleMakeCommand::class,
                ModuleExportCommand::class,
                ModuleImportCommand::class,
                PluginDiscoverCommand::class,
                PluginListCommand::class,
                PluginInstallCommand::class,
                PluginEnableCommand::class,
                PluginDisableCommand::class,
                PluginUninstallCommand::class,
                PluginMakeCommand::class,
                PluginLinkCommand::class,
                PluginExportCommand::class,
                PluginImportCommand::class,
                ThemeDiscoverCommand::class,
                ThemeListCommand::class,
                ThemeMakeCommand::class,
                ThemeInstallCommand::class,
                ThemeEnableCommand::class,
                ThemeSwitchCommand::class,
                ThemeDisableCommand::class,
                ThemeUninstallCommand::class,
                ThemeDeleteCommand::class,
                ThemeLinkCommand::class,
                ThemeExportCommand::class,
                ThemeImportCommand::class,
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
