<?php

namespace Starship\Core\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Plugins\StorageOss\Services\AliOssStorage;
use Starship\Core\Contracts\Storage\StorageInterface;
use Starship\Core\Models\Plugin as PluginModel;
use Starship\Core\Plugin\Plugin;
use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginLoader;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Tests\TestCase;

class StorageOssPluginLifecycleTest extends TestCase
{
    private const SLUG = 'storage-oss';

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir((string) config('starship.plugins_path').DIRECTORY_SEPARATOR.'StorageOss')) {
            $this->markTestSkipped('StorageOss 插件目录不存在，跳过验收测试。');
        }

        try {
            PluginModel::query()->where('slug', self::SLUG)->delete();
        } catch (\Throwable) {
            //
        }
    }

    protected function tearDown(): void
    {
        try {
            PluginModel::query()->where('slug', self::SLUG)->delete();
        } catch (\Throwable) {
            //
        }

        parent::tearDown();
    }

    public function test_storage_oss_plugin_full_lifecycle_via_cli(): void
    {
        $this->migrateStarshipTables();

        $this->app->singleton(StorageInterface::class, function () {
            return new class implements StorageInterface
            {
                public function put(string $path, mixed $content): bool
                {
                    return true;
                }

                public function get(string $path): string
                {
                    return '';
                }

                public function delete(string $path): bool
                {
                    return true;
                }

                public function url(string $path): string
                {
                    return '';
                }

                public function exists(string $path): bool
                {
                    return false;
                }
            };
        });
        $defaultStorage = $this->app->make(StorageInterface::class);

        $this->artisan('starship:plugin:install', ['plugin' => self::SLUG])->assertSuccessful();
        $this->artisan('starship:plugin:enable', ['plugin' => self::SLUG])->assertSuccessful();

        $record = PluginModel::query()->where('slug', self::SLUG)->first();
        $this->assertNotNull($record);
        $this->assertSame(Plugin::STATUS_ENABLED, (int) $record->status);

        $configRepository = $this->app->make(PluginConfigRepository::class);
        $configRepository->save(self::SLUG, [
            'access_key' => 'test-key',
            'secret_key' => 'test-secret',
            'bucket' => 'starship-test',
            'region' => 'cn-hangzhou',
            'endpoint' => 'https://oss-cn-hangzhou.aliyuncs.com',
            'cdn_url' => 'https://cdn.example.com',
            'prefix' => 'uploads',
        ]);

        $this->assertSame('starship-test', $configRepository->get(self::SLUG, 'bucket', ''));

        $plugin = $this->app->make(PluginManager::class)->findBySlug(self::SLUG);
        $this->assertNotNull($plugin);
        $this->assertTrue($plugin->isEnabled());

        $this->app->make(PluginLoader::class)->register($plugin);

        $this->assertInstanceOf(AliOssStorage::class, $this->app->make(StorageInterface::class));

        $uris = collect(Route::getRoutes()->getRoutes())
            ->map(static fn ($route) => $route->uri())
            ->all();

        $this->assertTrue(
            collect($uris)->contains(
                static fn (string $uri): bool => str_contains($uri, 'plugin/'.self::SLUG.'/test'),
            ),
            '启用后应注册插件测试路由，当前：'.implode(', ', $uris),
        );

        $this->artisan('starship:plugin:disable', ['plugin' => self::SLUG])->assertSuccessful();
        $record->refresh();
        $this->assertSame(Plugin::STATUS_DISABLED, (int) $record->status);

        $this->app->forgetInstance(StorageInterface::class);
        $this->app->singleton(StorageInterface::class, static fn () => $defaultStorage);
        $this->assertSame($defaultStorage, $this->app->make(StorageInterface::class));
        $this->assertNotInstanceOf(AliOssStorage::class, $this->app->make(StorageInterface::class));

        $this->artisan('starship:plugin:uninstall', ['plugin' => self::SLUG])->assertSuccessful();
        $record->refresh();
        $this->assertSame(Plugin::STATUS_DISCOVERED, (int) $record->status);
        $this->assertNull($record->installed_at);
        $this->assertNull($record->enabled_at);
    }

    public function test_cannot_uninstall_enabled_plugin(): void
    {
        $this->migrateStarshipTables();

        $this->artisan('starship:plugin:install', ['plugin' => self::SLUG])->assertSuccessful();
        $this->artisan('starship:plugin:enable', ['plugin' => self::SLUG])->assertSuccessful();

        $this->artisan('starship:plugin:uninstall', ['plugin' => self::SLUG])->assertFailed();
    }

    public function test_plugin_manager_lists_storage_oss_from_filesystem(): void
    {
        $plugin = $this->app->make(PluginManager::class)->findBySlug(self::SLUG);

        $this->assertNotNull($plugin);
        $this->assertSame('StorageOss', $plugin->name());
        $this->assertSame('storage', $plugin->type());
        $this->assertTrue($plugin->configPath() !== null);
        $this->assertGreaterThanOrEqual(2, count($plugin->pages()));
        $this->assertNotNull($plugin->configSchemaPath());
    }
}
