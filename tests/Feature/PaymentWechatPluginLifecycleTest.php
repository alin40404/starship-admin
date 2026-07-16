<?php

namespace Starship\Core\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Plugins\PaymentWechat\Services\WechatPayment;
use Starship\Core\Contracts\Payment\PaymentInterface;
use Starship\Core\Models\Plugin as PluginModel;
use Starship\Core\Plugin\Plugin;
use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginLoader;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Tests\TestCase;

class PaymentWechatPluginLifecycleTest extends TestCase
{
    private const SLUG = 'payment-wechat';

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir((string) config('starship.plugins_path').DIRECTORY_SEPARATOR.'PaymentWechat')) {
            $this->markTestSkipped('PaymentWechat 插件目录不存在，跳过验收测试。');
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

    public function test_payment_wechat_plugin_full_lifecycle_via_cli(): void
    {
        $this->migrateStarshipTables();

        $this->app->singleton(PaymentInterface::class, function () {
            return new class implements PaymentInterface
            {
                public function createOrder(array $data): array
                {
                    return [];
                }

                public function pay(string $orderNo): array
                {
                    return [];
                }

                public function refund(string $orderNo): array
                {
                    return [];
                }

                public function query(string $orderNo): array
                {
                    return [];
                }
            };
        });
        $defaultPayment = $this->app->make(PaymentInterface::class);

        $this->artisan('starship:plugin:install', ['plugin' => self::SLUG])->assertSuccessful();
        $this->artisan('starship:plugin:enable', ['plugin' => self::SLUG])->assertSuccessful();

        $record = PluginModel::query()->where('slug', self::SLUG)->first();
        $this->assertNotNull($record);
        $this->assertSame(Plugin::STATUS_ENABLED, (int) $record->status);

        $configRepository = $this->app->make(PluginConfigRepository::class);
        $configRepository->save(self::SLUG, [
            'app_id' => 'wx_test_app',
            'mch_id' => '1900000109',
            'api_v3_key' => 'test_api_v3_key_32chars_padding',
            'sandbox' => '1',
        ]);

        $this->assertSame('wx_test_app', $configRepository->get(self::SLUG, 'app_id', ''));

        $plugin = $this->app->make(PluginManager::class)->findBySlug(self::SLUG);
        $this->assertNotNull($plugin);
        $this->assertTrue($plugin->isEnabled());

        $this->app->make(PluginLoader::class)->register($plugin);

        $this->assertInstanceOf(WechatPayment::class, $this->app->make(PaymentInterface::class));

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

        $this->app->forgetInstance(PaymentInterface::class);
        $this->app->singleton(PaymentInterface::class, static fn () => $defaultPayment);
        $this->assertSame($defaultPayment, $this->app->make(PaymentInterface::class));
        $this->assertNotInstanceOf(WechatPayment::class, $this->app->make(PaymentInterface::class));

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

    public function test_plugin_manager_lists_payment_wechat_from_filesystem(): void
    {
        $plugin = $this->app->make(PluginManager::class)->findBySlug(self::SLUG);

        $this->assertNotNull($plugin);
        $this->assertSame('PaymentWechat', $plugin->name());
        $this->assertSame('payment', $plugin->type());
        $this->assertTrue($plugin->configPath() !== null);
        $this->assertGreaterThanOrEqual(2, count($plugin->pages()));
        $this->assertNotNull($plugin->configSchemaPath());
    }
}
