<?php

namespace Starship\Core\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Starship\Core\Models\Theme as ThemeModel;
use Starship\Core\Module\ModuleManager;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Providers\CoreServiceProvider;
use Starship\Core\Theme\ThemeLoader;
use Starship\Core\Theme\ThemeManager;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // mergeConfigFrom 会在 Provider::register 时覆盖路径，boot 后需再次写入
        $this->configureStarshipPaths($this->app);
        $this->configureAppKey($this->app);
        $this->app->forgetInstance(ModuleManager::class);
        $this->app->forgetInstance(PluginManager::class);
        $this->app->forgetInstance(ThemeManager::class);
    }

    protected function defineEnvironment($app): void
    {
        $this->configureTestingDatabase($app);
        $this->configureStarshipPaths($app);
        $this->configureAppKey($app);
        $this->configureMemberAuthGuard($app);
    }

    /**
     * 主题 Blade 使用 @auth('member') / route('member.*')，Testbench 默认无此 guard。
     */
    protected function configureMemberAuthGuard(mixed $app): void
    {
        $app['config']->set('auth.guards.member', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
    }

    /**
     * 注册主题模板渲染所需的最小命名路由（避免依赖 Member 模块）。
     */
    protected function registerThemeViewRoutes(): void
    {
        if (! Route::has('member.login')) {
            Route::get('/member/login', static fn () => 'login')->name('member.login');
        }
        if (! Route::has('member.register')) {
            Route::get('/member/register', static fn () => 'register')->name('member.register');
        }
        if (! Route::has('member.account')) {
            Route::get('/member/account', static fn () => 'account')->name('member.account');
        }
        if (! Route::has('member.logout')) {
            Route::post('/member/logout', static fn () => 'logout')->name('member.logout');
        }
        if (! Route::has('system.locale')) {
            Route::get('/locale/{locale}', static fn () => 'locale')->name('system.locale');
        }
    }

    /**
     * 清除 themes.is_active，使 ThemeLoader / theme_asset() 回退到 default_theme。
     * 无 sqlite 时复用本机 MySQL，避免被业务库当前激活主题污染断言。
     */
    protected function resetActiveThemesToDefault(): void
    {
        config([
            'starship.default_theme' => 'default',
            'starship.fallback_themes.frontend' => 'default',
        ]);

        try {
            if (Schema::hasTable('themes')) {
                ThemeModel::query()->update(['is_active' => 0]);
            }
        } catch (\Throwable) {
            // 无 themes 表或连接失败时忽略，走 config 回退
        }

        $this->app->forgetInstance(ThemeManager::class);
        $this->app->forgetInstance(ThemeLoader::class);
    }

    protected function configureAppKey(mixed $app): void
    {
        $key = 'base64:2fl+Ktvk6IJL0X+hqQ0jVELU3qPsuGeYvHWfTdR0gXQ=';
        $app['config']->set('app.key', $key);
    }

    protected function configureStarshipPaths(mixed $app): void
    {
        $repoRoot = dirname(__DIR__, 3);

        $app['config']->set('starship.repo_root', $repoRoot);
        $app['config']->set('starship.modules_path', $repoRoot.DIRECTORY_SEPARATOR.'modules');
        $app['config']->set('starship.plugins_path', $repoRoot.DIRECTORY_SEPARATOR.'plugins');
        $app['config']->set('starship.themes_path', $repoRoot.DIRECTORY_SEPARATOR.'themes');
        $app['config']->set(
            'starship.admin_frontend_src',
            $repoRoot.DIRECTORY_SEPARATOR.'apps'
                .DIRECTORY_SEPARATOR.'admin'
                .DIRECTORY_SEPARATOR.'web-antd'
                .DIRECTORY_SEPARATOR.'src'
        );
    }

    protected function configureTestingDatabase(mixed $app): void
    {
        if (in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $app['config']->set('database.default', 'testing');
            $app['config']->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);

            return;
        }

        if (! in_array('mysql', \PDO::getAvailableDrivers(), true)) {
            return;
        }

        $env = $this->readBackendEnv();
        if ($env === []) {
            return;
        }

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'mysql',
            'host' => $env['DB_HOST'] ?? '127.0.0.1',
            'port' => $env['DB_PORT'] ?? '3306',
            'database' => $env['DB_DATABASE'] ?? 'starship',
            'username' => $env['DB_USERNAME'] ?? 'root',
            'password' => $env['DB_PASSWORD'] ?? '',
            'charset' => $env['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $env['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix' => $env['DB_PREFIX'] ?? '',
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function readBackendEnv(): array
    {
        $envFile = dirname(__DIR__, 3).'/backend/.env';
        if (! is_file($envFile)) {
            return [];
        }

        $values = [];
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }

        return $values;
    }

    protected function requiresDatabase(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)
            && ! in_array('mysql', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('未安装 PDO sqlite/mysql 驱动，跳过需数据库的测试。');
        }

        if ($this->readBackendEnv() === [] && ! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('未找到 backend/.env，跳过需 MySQL 的测试。');
        }

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('数据库不可用：'.$exception->getMessage());
        }
    }

    protected function migrateStarshipTables(): void
    {
        $this->requiresDatabase();
        $this->artisan('migrate', ['--force' => true]);
    }
}
