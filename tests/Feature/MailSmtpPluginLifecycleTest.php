<?php

namespace Starship\Core\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Plugins\MailSmtp\Services\SmtpMailSender;
use Starship\Core\Contracts\Mail\MailSenderInterface;
use Starship\Core\Models\Plugin as PluginModel;
use Starship\Core\Plugin\Plugin;
use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginLoader;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Tests\TestCase;

class MailSmtpPluginLifecycleTest extends TestCase
{
    private const SLUG = 'mail-smtp';

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir((string) config('starship.plugins_path').DIRECTORY_SEPARATOR.'MailSmtp')) {
            $this->markTestSkipped('MailSmtp 插件目录不存在，跳过验收测试。');
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

    public function test_mail_smtp_plugin_full_lifecycle_via_cli(): void
    {
        $this->migrateStarshipTables();

        // 模拟 System 默认绑定（core 测试不加载 System 模块）
        $this->app->singleton(MailSenderInterface::class, function () {
            return new class implements MailSenderInterface
            {
                public function send(string $to, string $subject, string $body, array $options = []): void {}
            };
        });
        $this->assertTrue($this->app->bound(MailSenderInterface::class));
        $defaultSender = $this->app->make(MailSenderInterface::class);

        $this->artisan('starship:plugin:install', ['plugin' => self::SLUG])->assertSuccessful();
        $this->artisan('starship:plugin:enable', ['plugin' => self::SLUG])->assertSuccessful();

        $record = PluginModel::query()->where('slug', self::SLUG)->first();
        $this->assertNotNull($record);
        $this->assertSame(Plugin::STATUS_ENABLED, (int) $record->status);

        $configRepository = $this->app->make(PluginConfigRepository::class);
        $configRepository->save(self::SLUG, [
            'host' => 'smtp.example.com',
            'port' => '465',
            'username' => 'mailer@example.com',
            'password' => 'secret',
            'encryption' => 'ssl',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Starship',
        ]);

        $this->assertSame('smtp.example.com', $configRepository->get(self::SLUG, 'host', ''));
        $this->assertSame('mailer@example.com', $configRepository->get(self::SLUG, 'username', ''));

        $plugin = $this->app->make(PluginManager::class)->findBySlug(self::SLUG);
        $this->assertNotNull($plugin);
        $this->assertTrue($plugin->isEnabled());

        $this->app->make(PluginLoader::class)->register($plugin);

        $this->assertInstanceOf(SmtpMailSender::class, $this->app->make(MailSenderInterface::class));

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

        // 禁用后：新请求仅 System 默认绑定生效，此处模拟回落
        $this->app->forgetInstance(MailSenderInterface::class);
        $this->app->singleton(MailSenderInterface::class, static fn () => $defaultSender);
        $this->assertSame($defaultSender, $this->app->make(MailSenderInterface::class));
        $this->assertNotInstanceOf(SmtpMailSender::class, $this->app->make(MailSenderInterface::class));

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

    public function test_plugin_manager_lists_mail_smtp_from_filesystem(): void
    {
        $plugin = $this->app->make(PluginManager::class)->findBySlug(self::SLUG);

        $this->assertNotNull($plugin);
        $this->assertSame('MailSmtp', $plugin->name());
        $this->assertSame('email', $plugin->type());
        $this->assertTrue($plugin->configPath() !== null);
        $this->assertCount(2, $plugin->pages());
    }
}
