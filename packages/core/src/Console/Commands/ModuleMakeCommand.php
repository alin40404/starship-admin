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
                            {name : 模块名称（StudlyCase，如 Shop 或 Erp）}
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

        $modulePath = rtrim((string) config('starship.modules_path'), '/\\')
            .DIRECTORY_SEPARATOR.$studlyName;
        $slug = Str::kebab($studlyName);

        if (File::isDirectory($modulePath) && ! $this->option('force')) {
            $this->components->error("模块目录已存在：{$modulePath}，使用 --force 覆盖关键文件");

            return self::FAILURE;
        }

        try {
            $this->scaffoldModule($studlyName, $modulePath);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('模块已创建');
        $this->line('  '.$modulePath);
        $this->newLine();
        $this->components->twoColumnDetail('名称', $studlyName);
        $this->components->twoColumnDetail('Slug', $slug);

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

    protected function scaffoldModule(string $studlyName, string $modulePath): void
    {
        $slug = Str::kebab($studlyName);
        $providerClass = "{$studlyName}ServiceProvider";
        $providerNamespace = "Modules\\{$studlyName}\\App\\Providers";
        $providerFqn = "{$providerNamespace}\\{$providerClass}";

        File::ensureDirectoryExists($modulePath.'/app/Providers');
        File::ensureDirectoryExists($modulePath.'/routes');
        File::ensureDirectoryExists($modulePath.'/database/migrations');
        File::ensureDirectoryExists($modulePath.'/resources/lang/zh-CN');
        File::ensureDirectoryExists($modulePath.'/resources/lang/en');

        $this->writeFile(
            "{$modulePath}/composer.json",
            $this->composerJsonStub($studlyName, $slug),
            $this->option('force'),
        );

        $this->writeFile(
            "{$modulePath}/module.json",
            $this->moduleJsonStub($slug, $studlyName, $providerFqn),
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

        $gitkeep = "{$modulePath}/database/migrations/.gitkeep";
        if (! File::exists($gitkeep)) {
            File::put($gitkeep, '');
        }

        $this->writeFile(
            "{$modulePath}/resources/lang/zh-CN/messages.php",
            $this->langStub($studlyName),
            $this->option('force'),
        );

        $this->writeFile(
            "{$modulePath}/resources/lang/en/messages.php",
            $this->langStub($studlyName),
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

    protected function composerJsonStub(string $studlyName, string $slug): string
    {
        return <<<JSON
{
    "name": "starship/{$slug}",
    "description": "Starship Admin {$studlyName} module",
    "type": "library",
    "license": "MIT",
    "version": "0.1.0",
    "autoload": {
        "psr-4": {
            "Modules\\\\{$studlyName}\\\\App\\\\": "app/"
        }
    }
}

JSON;
    }

    protected function moduleJsonStub(string $slug, string $studlyName, string $providerFqn): string
    {
        $title = $studlyName;

        return <<<JSON
{
  "name": "{$slug}",
  "title": "{$title}",
  "version": "0.1.0",
  "enabled": false,
  "providers": [
    "{$providerFqn}"
  ],
  "seeders": [],
  "require": {
    "modules": ["system"]
  },
  "menus": {
    "paths": []
  },
  "permissions": {
    "prefixes": [
      "{$slug}."
    ],
    "exact": []
  }
}

JSON;
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

    protected function langStub(string $studlyName): string
    {
        return <<<PHP
<?php

return [
    'title' => '{$studlyName}',
];

PHP;
    }
}
