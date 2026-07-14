<?php

namespace Starship\Core\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Plugins\SmsAliyun\Services\AliyunSmsSender;
use Starship\Core\Contracts\Sms\SmsSenderInterface;
use Starship\Core\Models\Plugin as PluginModel;
use Starship\Core\Plugin\Plugin;
use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginLoader;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Tests\TestCase;

class SmsAliyunPluginLifecycleTest extends TestCase
{
    private const SLUG = 'sms-aliyun';

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir((string) config('starship.plugins_path').DIRECTORY_SEPARATOR.'SmsAliyun')) {
            $this->markTestSkipped('SmsAliyun 插件目录不存在，跳过验收测试。');
        }
    }

    protected function tearDown(): void
    {
        try {
            PluginModel::query()->where('slug', self::SLUG)->delete();
        } catch (\Throwable) {
            // 数据库未配置时忽略清理
        }

        parent::tearDown();
    }

    public function test_sms_aliyun_plugin_full_lifecycle_via_cli(): void
    {
        $this->migrateStarshipTables();

        // 安装时自动发现入库，无需先执行 discover
        $this->artisan('starship:plugin:install', ['plugin' => self::SLUG])->assertSuccessful();
        $this->artisan('starship:plugin:enable', ['plugin' => self::SLUG])->assertSuccessful();

        $record = PluginModel::query()->where('slug', self::SLUG)->first();
        $this->assertNotNull($record);
        $this->assertSame(Plugin::STATUS_ENABLED, (int) $record->status);

        $configRepository = $this->app->make(PluginConfigRepository::class);
        $configRepository->save(self::SLUG, [
            'access_key' => 'test-ak',
            'secret_key' => 'test-sk',
            'sign_name' => '测试签名',
            'template_code' => 'SMS_001',
        ]);

        $this->assertSame('test-ak', $configRepository->get(self::SLUG, 'access_key', ''));
        $this->assertSame('测试签名', $configRepository->get(self::SLUG, 'sign_name', ''));

        $plugin = $this->app->make(PluginManager::class)->findBySlug(self::SLUG);
        $this->assertNotNull($plugin);
        $this->assertTrue($plugin->isEnabled());

        // 模拟新请求由 PluginLoader 注册已启用插件路由与 Contract 绑定
        $this->app->make(PluginLoader::class)->register($plugin);

        $this->assertInstanceOf(AliyunSmsSender::class, $this->app->make(SmsSenderInterface::class));

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

    public function test_install_auto_discovers_missing_record(): void
    {
        $this->migrateStarshipTables();

        $this->assertNull(PluginModel::query()->where('slug', self::SLUG)->first());

        $this->artisan('starship:plugin:install', ['plugin' => self::SLUG])->assertSuccessful();

        $record = PluginModel::query()->where('slug', self::SLUG)->first();
        $this->assertNotNull($record);
        $this->assertSame(Plugin::STATUS_INSTALLED, (int) $record->status);
        $this->assertNotNull($record->installed_at);
    }

    public function test_plugin_manager_lists_sms_aliyun_from_filesystem(): void
    {
        $plugin = $this->app->make(PluginManager::class)->findBySlug(self::SLUG);

        $this->assertNotNull($plugin);
        $this->assertSame('SmsAliyun', $plugin->name());
        $this->assertSame('adapter', $plugin->type());
        $this->assertTrue($plugin->configPath() !== null);
        $this->assertCount(2, $plugin->pages());
    }
}
