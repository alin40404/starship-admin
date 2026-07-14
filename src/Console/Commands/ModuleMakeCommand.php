<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Starship\Core\Console\Concerns\LinksModuleToComposer;

class ModuleMakeCommand extends Command
{
    use LinksModuleToComposer;

    protected $signature = 'starship:module:make
                            {name : 模块名称（可小写，如 shop / Shop；目录与命名空间均为 StudlyCase）}
                            {--title= : 显示名称，写入 module.json title 与语言包，默认与模块名相同}
                            {--force : 覆盖已存在的 module.json 与 Provider}
                            {--no-composer : 不自动改 backend/composer.json / 不跑 composer update}
                            {--no-update : 只写 composer.json，不执行 composer update}';

    protected $description = '创建新的 Starship 业务模块脚手架';

    public function handle(): int
    {
        $studlyName = Str::studly((string) $this->argument('name'));
        if ($studlyName === '') {
            $this->components->error('模块名称不能为空');

            return self::FAILURE;
        }

        $title = trim((string) $this->option('title'));
        if ($title === '') {
            $title = $studlyName;
        }

        $modulePath = rtrim((string) config('starship.modules_path'), '/\\')
            .DIRECTORY_SEPARATOR.$studlyName;
        $slug = Str::kebab($studlyName);

        if (File::isDirectory($modulePath) && ! $this->option('force')) {
            $this->components->error("模块目录已存在：{$modulePath}，使用 --force 覆盖关键文件");

            return self::FAILURE;
        }

        try {
            $this->scaffoldModule($studlyName, $modulePath, $title);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('模块已创建');
        $this->line('  '.$modulePath);
        $this->newLine();
        $this->components->twoColumnDetail('名称', $studlyName);
        $this->components->twoColumnDetail('Slug', $slug);
        $this->components->twoColumnDetail('标题', $title);

        if (! $this->option('no-composer')) {
            $link = $this->linkModuleToComposer(
                $modulePath,
                ! $this->option('no-update'),
            );
            $this->reportComposerLink($link);
        }

        $this->newLine();
        $this->components->info('建议下一步');
        $next = ['将 module.json 中 enabled 设为 true 后重启服务'];
        if ($this->option('no-composer')) {
            array_unshift(
                $next,
                "在 backend/composer.json 的 repositories 添加：{\"type\":\"path\",\"url\":\"../modules/{$studlyName}\"}",
                "在 require 添加：\"starship/{$slug}\": \"@dev\"",
                '执行 composer update starship/'.$slug,
            );
        }
        $this->components->bulletList($next);

        return self::SUCCESS;
    }

    protected function scaffoldModule(string $studlyName, string $modulePath, string $title): void
    {
        $slug = Str::kebab($studlyName);
        $providerClass = "{$studlyName}ServiceProvider";
        $providerNamespace = "Modules\\{$studlyName}\\App\\Providers";
        $providerFqn = "{$providerNamespace}\\{$providerClass}";

        File::ensureDirectoryExists($modulePath.'/app/Providers');
        File::ensureDirectoryExists($modulePath.'/routes');
        File::ensureDirectoryExists($modulePath.'/database/migrations');
        File::ensureDirectoryExists($modulePath.'/database/seeders');
        File::ensureDirectoryExists($modulePath.'/resources/lang/zh-CN');
        File::ensureDirectoryExists($modulePath.'/resources/lang/en');

        $this->writeFile(
            "{$modulePath}/composer.json",
            $this->composerJsonStub($studlyName, $slug, $title),
            $this->option('force'),
        );

        $this->writeFile(
            "{$modulePath}/module.json",
            $this->moduleJsonStub($slug, $title, $providerFqn),
            $this->option('force'),
        );

        $this->writeFile(
            "{$modulePath}/app/Providers/{$providerClass}.php",
            $this->providerStub($studlyName, $providerNamespace, $providerClass, $slug),
            $this->option('force'),
        );

        $this->writeFile(
            "{$modulePath}/routes/admin.php",
            $this->routesStub($studlyName, $slug),
            $this->option('force'),
        );

        foreach (['database/migrations/.gitkeep', 'database/seeders/.gitkeep'] as $relative) {
            $gitkeep = "{$modulePath}/{$relative}";
            if (! File::exists($gitkeep)) {
                File::put($gitkeep, '');
            }
        }

        $this->writeFile(
            "{$modulePath}/resources/lang/zh-CN/messages.php",
            $this->langStub($title),
            $this->option('force'),
        );

        $this->writeFile(
            "{$modulePath}/resources/lang/en/messages.php",
            $this->langStub($title),
            $this->option('force'),
        );
    }

    protected function writeFile(string $path, string $content, bool $force): void
    {
        if (File::exists($path) && ! $force) {
            return;
        }

        File::put($path, $content);
    }

    protected function composerJsonStub(string $studlyName, string $slug, string $title): string
    {
        return json_encode([
            'name' => "starship/{$slug}",
            'description' => "Starship Admin {$title} module",
            'type' => 'library',
            'license' => 'MIT',
            'version' => '0.1.0',
            'autoload' => [
                'psr-4' => [
                    "Modules\\{$studlyName}\\App\\" => 'app/',
                    "Modules\\{$studlyName}\\Database\\Seeders\\" => 'database/seeders/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    "Modules\\{$studlyName}\\Tests\\" => 'tests/',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    }

    protected function moduleJsonStub(string $slug, string $title, string $providerFqn): string
    {
        return json_encode([
            'name' => $slug,
            'title' => $title,
            'version' => '0.1.0',
            'enabled' => false,
            'providers' => [$providerFqn],
            'seeders' => [],
            'require' => [
                'modules' => ['system'],
            ],
            'menus' => [
                'paths' => [],
            ],
            'permissions' => [
                'prefixes' => ["{$slug}."],
                'exact' => [],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    }

    protected function providerStub(
        string $studlyName,
        string $providerNamespace,
        string $providerClass,
        string $slug,
    ): string {
        return <<<PHP
<?php

namespace {$providerNamespace};

use Illuminate\Support\ServiceProvider;

class {$providerClass} extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \$this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        \$this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        \$this->loadTranslationsFrom(__DIR__.'/../../resources/lang', '{$slug}');
    }
}

PHP;
    }

    protected function routesStub(string $studlyName, string $slug): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('{$slug}')->group(function () {
        // Route::get('ping', fn () => response()->json(['module' => '{$slug}']));
    });
});

PHP;
    }

    protected function langStub(string $title): string
    {
        $exported = var_export($title, true);

        return <<<PHP
<?php

return [
    'title' => {$exported},
];

PHP;
    }
}
