<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;
use Starship\Core\Install\InstallService;
use Throwable;

class InstallCommand extends Command
{
    protected $signature = 'starship:install
                            {--force : 非交互模式}
                            {--skip-database-config : 跳过数据库配置，直接使用当前 .env}
                            {--db-connection= : 数据库类型 mysql|sqlite|pgsql}
                            {--db-host= : 数据库主机}
                            {--db-port= : 数据库端口}
                            {--db-database= : 数据库名或 sqlite 路径}
                            {--db-username= : 数据库用户名}
                            {--db-password= : 数据库密码}
                            {--db-prefix= : 表前缀}
                            {--app-url= : 站点 URL}
                            {--site-name= : 站点名称}
                            {--admin-username=admin : 超级管理员用户名}
                            {--admin-password= : 超级管理员密码}
                            {--admin-email=admin@starship.org : 超级管理员邮箱}';

    protected $description = '项目安装引导：环境检测、数据库配置、迁移与数据初始化';

    public function handle(InstallService $install): int
    {
        $status = $install->status();
        if ($status['installed']) {
            $this->components->error('项目已安装。如需重装，请先删除 '.$status['lock_path']);

            return self::FAILURE;
        }

        try {
            $requirements = $install->checkEnvironment();
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->renderRequirements($requirements);
        if (! $requirements['passed']) {
            $this->components->error('环境检测未通过，请先修复后再执行安装');

            return self::FAILURE;
        }

        $this->components->info('环境检测通过');

        if (! $this->option('skip-database-config')) {
            try {
                $config = $this->resolveDatabaseConfig();
                $saved = $install->saveDatabaseConfig($config);
                $this->components->info('数据库配置已保存：'.$saved['connection'].' / '.$saved['database']);
            } catch (InvalidArgumentException|RuntimeException $exception) {
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            } catch (Throwable $exception) {
                $this->components->error('数据库配置失败：'.$exception->getMessage());

                return self::FAILURE;
            }
        } else {
            $this->components->info('已跳过数据库配置，使用当前 .env');
        }

        try {
            $admin = $this->resolveAdminConfig();
            $result = $this->runInstallWithProgress($install, $admin);
            $this->newLine();
            $this->components->info('安装完成');
            $this->components->twoColumnDetail('管理员账号', $result['admin_username']);
            $this->components->twoColumnDetail('管理员邮箱', $admin['email']);
            $this->components->twoColumnDetail('安装锁', $status['lock_path']);

            return self::SUCCESS;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->components->error('安装失败：'.$exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array{site_name: string, username: string, password: string, email: string}  $admin
     * @return array{installed: bool, admin_username: string, percent: int}
     */
    protected function runInstallWithProgress(InstallService $install, array $admin): array
    {
        $labels = [
            'prepare' => '写入环境配置',
            'migrate' => '执行数据库迁移',
            'seed' => '初始化基础数据',
            'finalize' => '完成收尾与锁定',
        ];

        $total = count(InstallService::INSTALL_STEPS);
        $result = [
            'installed' => false,
            'admin_username' => $admin['username'],
            'percent' => 0,
        ];

        $this->newLine();
        $this->components->info('开始安装');

        foreach (InstallService::INSTALL_STEPS as $index => $step) {
            $label = $labels[$step] ?? $step;
            $percent = InstallService::INSTALL_STEP_PERCENT[$step] ?? 0;
            $title = sprintf('(%d/%d) %s [%d%%]', $index + 1, $total, $label, $percent);

            $this->components->task($title, function () use ($install, $step, $admin, &$result): void {
                $stepResult = $install->runInstallStep($step, $admin);
                $result = [
                    'installed' => (bool) ($stepResult['installed'] ?? false),
                    'admin_username' => (string) ($stepResult['admin_username'] ?? ''),
                    'percent' => (int) ($stepResult['percent'] ?? 0),
                ];
            });
        }

        return $result;
    }

    /**
     * @param  array{
     *   passed: bool,
     *   php: array{label: string, required: string, current: string, passed: bool},
     *   extensions: list<array{name: string, required: bool, loaded: bool, passed: bool}>,
     *   directories: list<array{path: string, writable: bool, passed: bool}>
     * }  $requirements
     */
    protected function renderRequirements(array $requirements): void
    {
        $php = $requirements['php'];
        $this->components->twoColumnDetail(
            $php['label'].' '.$php['required'],
            ($php['passed'] ? '<fg=green>OK</>' : '<fg=red>FAIL</>').' ('.$php['current'].')'
        );

        foreach ($requirements['extensions'] as $extension) {
            $label = $extension['name'].($extension['required'] ? '' : ' (optional)');
            $status = $extension['loaded']
                ? '<fg=green>OK</>'
                : ($extension['required'] ? '<fg=red>MISSING</>' : '<fg=yellow>MISSING</>');
            $this->components->twoColumnDetail($label, $status);
        }

        foreach ($requirements['directories'] as $directory) {
            $this->components->twoColumnDetail(
                $directory['path'],
                $directory['writable'] ? '<fg=green>writable</>' : '<fg=red>not writable</>'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveDatabaseConfig(): array
    {
        $force = (bool) $this->option('force');

        $connection = (string) ($this->option('db-connection') ?: ($force
            ? (env('DB_CONNECTION') ?: 'sqlite')
            : $this->choice('数据库类型', ['mysql', 'sqlite', 'pgsql'], env('DB_CONNECTION') ?: 'mysql')));

        if ($connection === 'sqlite') {
            $database = (string) ($this->option('db-database') ?: ($force
                ? (env('DB_DATABASE') ?: 'database/database.sqlite')
                : $this->ask('SQLite 数据库文件路径', env('DB_DATABASE') ?: 'database/database.sqlite')));

            return [
                'connection' => 'sqlite',
                'database' => $database,
                'app_url' => $this->resolveAppUrl($force),
            ];
        }

        return [
            'connection' => $connection,
            'host' => (string) ($this->option('db-host') ?: ($force
                ? (env('DB_HOST') ?: '127.0.0.1')
                : $this->ask('数据库主机', env('DB_HOST') ?: '127.0.0.1'))),
            'port' => (string) ($this->option('db-port') ?: ($force
                ? (env('DB_PORT') ?: ($connection === 'pgsql' ? '5432' : '3306'))
                : $this->ask('数据库端口', env('DB_PORT') ?: ($connection === 'pgsql' ? '5432' : '3306')))),
            'database' => (string) ($this->option('db-database') ?: ($force
                ? (env('DB_DATABASE') ?: 'starship_admin')
                : $this->ask('数据库名', env('DB_DATABASE') ?: 'starship_admin'))),
            'username' => (string) ($this->option('db-username') ?: ($force
                ? (env('DB_USERNAME') ?: 'root')
                : $this->ask('数据库用户名', env('DB_USERNAME') ?: 'root'))),
            'password' => (string) ($this->option('db-password') ?? ($force
                ? (string) env('DB_PASSWORD', '')
                : (string) $this->ask('数据库密码（可留空）', ''))),
            'prefix' => $this->option('db-prefix') !== null
                ? (string) $this->option('db-prefix')
                : ($force ? env('DB_PREFIX') : $this->ask('表前缀', env('DB_PREFIX') ?: 'sa_')),
            'app_url' => $this->resolveAppUrl($force),
        ];
    }

    protected function resolveAppUrl(bool $force): string
    {
        return (string) ($this->option('app-url') ?: ($force
            ? (env('APP_URL') ?: 'http://127.0.0.1:8000')
            : $this->ask('站点 URL', env('APP_URL') ?: 'http://127.0.0.1:8000')));
    }

    /**
     * @return array{site_name: string, username: string, password: string, email: string}
     */
    protected function resolveAdminConfig(): array
    {
        $force = (bool) $this->option('force');
        $siteName = (string) ($this->option('site-name') ?: env('APP_NAME', 'Starship Admin'));
        $username = (string) ($this->option('admin-username') ?: 'admin');
        $email = (string) ($this->option('admin-email') ?: 'admin@starship.org');
        $password = (string) ($this->option('admin-password') ?: 'admin123');

        if (! $force) {
            $siteName = (string) $this->ask('站点名称', $siteName);
            $username = (string) $this->ask('超级管理员用户名', $username);
            $email = (string) $this->ask('超级管理员邮箱', $email);
            $password = (string) $this->ask('超级管理员密码', $password);
        }

        return [
            'site_name' => $siteName,
            'username' => $username,
            'password' => $password,
            'email' => $email,
        ];
    }
}
