<?php

namespace Starship\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PluginMakeCommand extends Command
{
    /**
     * @var list<string>
     */
    private const VALID_TYPES = [
        'adapter',
        'ai',
        'email',
        'map',
        'oauth',
        'payment',
        'search',
        'sms',
        'storage',
    ];

    protected $signature = 'starship:plugin:make
                            {name : 插件名称（StudlyCase，如 SmsAliyun 或 PaymentWechat）}
                            {--type=adapter : 插件类型（payment/storage/sms 等）}
                            {--slug= : 插件 slug，默认由名称推导}
                            {--description= : 插件描述}
                            {--force : 覆盖已存在的 plugin.json 与 Provider}';

    protected $description = '创建新的 Starship 插件脚手架';

    public function handle(): int
    {
        $studlyName = Str::studly((string) $this->argument('name'));
        if ($studlyName === '') {
            $this->error('插件名称不能为空');

            return self::FAILURE;
        }

        $type = strtolower(trim((string) $this->option('type')));
        if (! in_array($type, self::VALID_TYPES, true)) {
            $this->error('无效的插件类型，可选：'.implode(', ', self::VALID_TYPES));

            return self::FAILURE;
        }

        $slug = trim((string) $this->option('slug'));
        if ($slug === '') {
            $slug = Str::kebab($studlyName);
        }

        $description = trim((string) $this->option('description'));
        if ($description === '') {
            $description = "Starship {$studlyName} 插件";
        }

        $pluginsPath = rtrim((string) config('starship.plugins_path'), '/\\');
        $pluginPath = $pluginsPath.DIRECTORY_SEPARATOR.$studlyName;

        if (File::isDirectory($pluginPath) && ! $this->option('force')) {
            $this->error("插件目录已存在：{$pluginPath}，使用 --force 覆盖关键文件");

            return self::FAILURE;
        }

        try {
            $this->scaffoldPlugin($studlyName, $slug, $type, $description, $pluginPath);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("插件 {$studlyName} 已创建：{$pluginPath}");
        $this->line('下一步：');
        $this->line("  1. 执行 php artisan starship:plugin:install {$slug}（会自动发现入库）");
        $this->line("  2. 执行 php artisan starship:plugin:enable {$slug}");
        $this->line('  3. 重启后端服务后在管理端「插件管理」中配置');

        return self::SUCCESS;
    }

    protected function scaffoldPlugin(
        string $studlyName,
        string $slug,
        string $type,
        string $description,
        string $pluginPath,
    ): void {
        $providerClass = "{$studlyName}ServiceProvider";
        $providerNamespace = "Plugins\\{$studlyName}\\Providers";
        $providerFqn = "{$providerNamespace}\\{$providerClass}";
        $envPrefix = strtoupper(str_replace('-', '_', $slug));

        File::ensureDirectoryExists($pluginPath.'/src/Providers');
        File::ensureDirectoryExists($pluginPath.'/config');
        File::ensureDirectoryExists($pluginPath.'/routes');
        File::ensureDirectoryExists($pluginPath.'/resources/lang/zh_CN');
        File::ensureDirectoryExists($pluginPath.'/resources/lang/en');

        $this->writeFile(
            "{$pluginPath}/plugin.json",
            $this->pluginJsonStub($studlyName, $slug, $type, $description, $providerFqn),
            $this->option('force'),
        );

        $this->writeFile(
            "{$pluginPath}/src/Providers/{$providerClass}.php",
            $this->providerStub($providerNamespace, $providerClass, $slug),
            $this->option('force'),
        );

        $this->writeFile(
            "{$pluginPath}/config/{$slug}.php",
            $this->configStub($envPrefix),
            $this->option('force'),
        );

        $this->writeFile(
            "{$pluginPath}/routes/admin-api.php",
            $this->routesStub($slug),
            $this->option('force'),
        );

        $this->writeFile(
            "{$pluginPath}/resources/lang/zh_CN/messages.php",
            $this->langStubZh(),
            $this->option('force'),
        );

        $this->writeFile(
            "{$pluginPath}/resources/lang/en/messages.php",
            $this->langStubEn(),
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

    protected function pluginJsonStub(
        string $studlyName,
        string $slug,
        string $type,
        string $description,
        string $providerFqn,
    ): string {
        $manifest = [
            'name' => $studlyName,
            'slug' => $slug,
            'type' => $type,
            'namespace' => "Plugins\\{$studlyName}",
            'version' => '1.0.0',
            'description' => $description,
            'author' => 'Starship Admin',
            'require' => [
                'starship' => '>=1.0.0',
                'php' => '>=8.3',
                'modules' => ['System'],
                'plugins' => [],
            ],
            'providers' => [$providerFqn],
            'routes' => ['routes/admin-api.php'],
            'migrations' => false,
            'config' => "config/{$slug}.php",
            'pages' => [
                [
                    'key' => 'config',
                    'title' => '配置',
                    'permission' => 'system.plugins.config',
                ],
            ],
            'enabled' => false,
            'order' => 500,
        ];

        $json = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        if ($json === false) {
            throw new InvalidArgumentException('生成 plugin.json 失败');
        }

        return $json;
    }

    protected function providerStub(
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
        \$this->loadTranslationsFrom(__DIR__.'/../../resources/lang', '{$slug}');
    }
}

PHP;
    }

    protected function configStub(string $envPrefix): string
    {
        return <<<PHP
<?php

return [
    'api_key' => env('PLUGIN_{$envPrefix}_API_KEY', ''),
];

PHP;
    }

    protected function routesStub(string $slug): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('admin-api')->group(function () {
    Route::middleware('auth:sanctum')->prefix('plugin/{$slug}')->group(function () {
        // Route::get('ping', fn () => response()->json(['plugin' => '{$slug}']));
    });
});

PHP;
    }

    protected function langStubZh(): string
    {
        return <<<'PHP'
<?php

return [
    'config_incomplete' => '请先完善插件配置',
];

PHP;
    }

    protected function langStubEn(): string
    {
        return <<<'PHP'
<?php

return [
    'config_incomplete' => 'Please complete the plugin configuration first.',
];

PHP;
    }
}
