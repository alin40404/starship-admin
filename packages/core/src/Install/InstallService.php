<?php

namespace Starship\Core\Install;

use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class InstallService
{
    public function __construct(
        protected InstallLock $lock,
        protected EnvironmentChecker $environmentChecker,
        protected EnvWriter $envWriter,
    ) {}

    /**
     * @return array{installed: bool, lock_path: string}
     */
    public function status(): array
    {
        return [
            'installed' => $this->lock->isInstalled(),
            'lock_path' => 'storage/app/starship.installed',
        ];
    }

    /**
     * @return array{
     *   passed: bool,
     *   php: array{label: string, required: string, current: string, passed: bool},
     *   extensions: list<array{name: string, required: bool, loaded: bool, passed: bool}>,
     *   directories: list<array{path: string, writable: bool, passed: bool}>
     * }
     */
    public function checkEnvironment(): array
    {
        $this->lock->assertNotInstalled();

        return $this->environmentChecker->check();
    }

    /**
     * @param  array{
     *   connection?: string,
     *   host?: string|null,
     *   port?: string|int|null,
     *   database?: string|null,
     *   username?: string|null,
     *   password?: string|null,
     *   prefix?: string|null,
     *   app_url?: string|null,
     *   cors_allowed_origins?: string|null
     * }  $config
     * @return array{connection: string, database: string}
     */
    public function saveDatabaseConfig(array $config): array
    {
        $this->lock->assertNotInstalled();

        $requirements = $this->environmentChecker->check();
        if (! $requirements['passed']) {
            throw new RuntimeException('环境检测未通过，请先修复后再配置数据库');
        }

        $connection = strtolower((string) ($config['connection'] ?? 'mysql'));
        if (! in_array($connection, ['mysql', 'sqlite', 'pgsql'], true)) {
            throw new InvalidArgumentException('不支持的数据库类型：'.$connection);
        }

        $appUrl = trim((string) ($config['app_url'] ?? ''));
        if ($appUrl === '') {
            throw new InvalidArgumentException('请填写站点 URL');
        }
        if (filter_var($appUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('站点 URL 格式不正确');
        }

        $this->envWriter->ensureEnvFile();

        $values = [
            'DB_CONNECTION' => $connection,
            'APP_URL' => $appUrl,
        ];

        if (! empty($config['cors_allowed_origins'])) {
            $values['CORS_ALLOWED_ORIGINS'] = (string) $config['cors_allowed_origins'];
        }

        if ($connection === 'sqlite') {
            $database = trim((string) ($config['database'] ?? ''));
            if ($database === '') {
                throw new InvalidArgumentException('请填写 SQLite 文件路径');
            }
            $absolute = $this->resolveSqlitePath($database);
            File::ensureDirectoryExists(dirname($absolute));
            if (! File::exists($absolute)) {
                File::put($absolute, '');
            }

            $values['DB_DATABASE'] = $absolute;
            $values['DB_HOST'] = null;
            $values['DB_PORT'] = null;
            $values['DB_USERNAME'] = null;
            $values['DB_PASSWORD'] = null;
        } else {
            $host = trim((string) ($config['host'] ?? ''));
            $port = trim((string) ($config['port'] ?? ''));
            $database = trim((string) ($config['database'] ?? ''));
            $username = trim((string) ($config['username'] ?? ''));

            if ($host === '') {
                throw new InvalidArgumentException('请填写数据库主机');
            }
            if ($port === '') {
                throw new InvalidArgumentException('请填写数据库端口');
            }
            if (! is_numeric($port)) {
                throw new InvalidArgumentException('数据库端口必须是数字');
            }
            if ($database === '') {
                throw new InvalidArgumentException('请填写数据库名');
            }
            if ($username === '') {
                throw new InvalidArgumentException('请填写数据库用户名');
            }

            $values['DB_HOST'] = $host;
            $values['DB_PORT'] = $port;
            $values['DB_DATABASE'] = $database;
            $values['DB_USERNAME'] = $username;
            $values['DB_PASSWORD'] = (string) ($config['password'] ?? '');
        }

        if (array_key_exists('prefix', $config) && $config['prefix'] !== null) {
            $values['DB_PREFIX'] = (string) $config['prefix'];
        }

        $this->ensureAppKey();
        $this->envWriter->setMany($values);
        $this->applyRuntimeDatabaseConfig($connection, $values);
        $this->testDatabaseConnection($connection);

        Artisan::call('config:clear');

        return [
            'connection' => $connection,
            'database' => (string) ($values['DB_DATABASE'] ?? ''),
        ];
    }

    /**
     * @param  array{
     *   username?: string,
     *   password?: string,
     *   email?: string|null
     * }  $admin
     * @return array{installed: bool, admin_username: string}
     */
    public function runInstall(array $admin = []): array
    {
        $this->lock->assertNotInstalled();

        $requirements = $this->environmentChecker->check();
        if (! $requirements['passed']) {
            throw new RuntimeException('环境检测未通过，无法继续安装');
        }

        $username = trim((string) ($admin['username'] ?? ''));
        if ($username === '') {
            throw new InvalidArgumentException('请填写超级管理员用户名');
        }

        $password = (string) ($admin['password'] ?? '');
        if ($password === '') {
            throw new InvalidArgumentException('请设置超级管理员密码');
        }
        if (strlen($password) < 6) {
            throw new InvalidArgumentException('超级管理员密码至少 6 位');
        }

        $email = trim((string) ($admin['email'] ?? ''));
        if ($email === '') {
            throw new InvalidArgumentException('请填写超级管理员邮箱');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('超级管理员邮箱格式不正确');
        }

        $this->ensureAppKey();
        $this->envWriter->setMany([
            'SYSTEM_ADMIN_USERNAME' => $username,
            'SYSTEM_ADMIN_PASSWORD' => $password,
            'SYSTEM_ADMIN_EMAIL' => $email,
        ]);

        // 当前进程立即可见（供 Seeder 读取）
        putenv('SYSTEM_ADMIN_USERNAME='.$username);
        putenv('SYSTEM_ADMIN_PASSWORD='.$password);
        putenv('SYSTEM_ADMIN_EMAIL='.$email);
        $_ENV['SYSTEM_ADMIN_USERNAME'] = $username;
        $_ENV['SYSTEM_ADMIN_PASSWORD'] = $password;
        $_ENV['SYSTEM_ADMIN_EMAIL'] = $email;
        $_SERVER['SYSTEM_ADMIN_USERNAME'] = $username;
        $_SERVER['SYSTEM_ADMIN_PASSWORD'] = $password;
        $_SERVER['SYSTEM_ADMIN_EMAIL'] = $email;

        $wasProduction = app()->isProduction();
        $this->allowDestructiveCommands();

        try {
            $migrate = Artisan::call('migrate', ['--force' => true]);
            if ($migrate !== 0) {
                throw new RuntimeException('数据库迁移失败：'.Artisan::output());
            }

            $seed = Artisan::call('db:seed', ['--force' => true]);
            if ($seed !== 0) {
                throw new RuntimeException('数据初始化失败：'.Artisan::output());
            }

            Artisan::call('storage:link', ['--force' => true]);
        } catch (Throwable $exception) {
            throw new RuntimeException('安装失败：'.$exception->getMessage(), 0, $exception);
        } finally {
            $this->restoreDestructiveCommands($wasProduction);
        }

        $this->lock->lock();

        return [
            'installed' => true,
            'admin_username' => $username,
        ];
    }

    protected function ensureAppKey(): void
    {
        $this->envWriter->ensureEnvFile();
        $current = (string) config('app.key', '');
        $envKey = (string) (env('APP_KEY') ?: '');

        if ($current !== '' || $envKey !== '') {
            return;
        }

        $key = 'base64:'.base64_encode(Str::random(32));
        $this->envWriter->set('APP_KEY', $key);
        config(['app.key' => $key]);
    }

    /**
     * @param  array<string, scalar|null>  $values
     */
    protected function applyRuntimeDatabaseConfig(string $connection, array $values): void
    {
        config(['database.default' => $connection]);

        if ($connection === 'sqlite') {
            config([
                'database.connections.sqlite.database' => $values['DB_DATABASE'],
                'database.connections.sqlite.prefix' => (string) ($values['DB_PREFIX'] ?? config('database.connections.sqlite.prefix', '')),
            ]);
        } else {
            config([
                "database.connections.{$connection}.host" => $values['DB_HOST'] ?? '127.0.0.1',
                "database.connections.{$connection}.port" => $values['DB_PORT'] ?? null,
                "database.connections.{$connection}.database" => $values['DB_DATABASE'] ?? null,
                "database.connections.{$connection}.username" => $values['DB_USERNAME'] ?? null,
                "database.connections.{$connection}.password" => $values['DB_PASSWORD'] ?? '',
                "database.connections.{$connection}.prefix" => (string) ($values['DB_PREFIX'] ?? config("database.connections.{$connection}.prefix", '')),
            ]);
        }
    }

    protected function testDatabaseConnection(string $connection): void
    {
        try {
            DB::purge($connection);
            DB::connection($connection)->getPdo();
            DB::connection($connection)->select('select 1');
        } catch (Throwable $exception) {
            throw new RuntimeException('数据库连接失败：'.$exception->getMessage(), 0, $exception);
        }
    }

    protected function resolveSqlitePath(string $database): string
    {
        if ($database === ':memory:') {
            return $database;
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $database) === 1 || str_starts_with($database, '/')) {
            return $database;
        }

        return base_path($database);
    }

    protected function allowDestructiveCommands(): void
    {
        DB::prohibitDestructiveCommands(false);
        SeedCommand::prohibit(false);
    }

    protected function restoreDestructiveCommands(bool $wasProduction): void
    {
        DB::prohibitDestructiveCommands($wasProduction);
        SeedCommand::prohibit($wasProduction);
    }
}
