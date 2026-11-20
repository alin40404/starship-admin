<?php

namespace Tests\Feature;

use Modules\System\App\Support\Sms\SmsGateway;
use Modules\System\App\Support\Storage\LocalStorage;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Sms\SmsSenderInterface;
use Starship\Core\Contracts\Storage\StorageInterface;
use Starship\Core\Module\ModuleManager;
use Tests\TestCase;

/**
 * backend 应用壳冒烟：不依赖业务库种子，覆盖启动后核心能力。
 */
class FrameworkSmokeTest extends TestCase
{
    #[Test]
    public function admin_api_ping_is_ok(): void
    {
        $this->getJson('/admin-api/ping')
            ->assertOk()
            ->assertJsonPath('message', 'Starship Admin is running');
    }

    #[Test]
    public function enabled_modules_include_core_business_modules(): void
    {
        $names = app(ModuleManager::class)
            ->enabled()
            ->map(static fn ($module) => strtolower($module->name()))
            ->values()
            ->all();

        $this->assertContains('system', $names);
        $this->assertContains('generator', $names);
        $this->assertContains('member', $names);
    }

    #[Test]
    public function plugins_directory_contains_sms_aliyun_manifest(): void
    {
        $path = (string) config('starship.plugins_path');
        $this->assertNotSame('', $path);
        $this->assertDirectoryExists($path);

        $manifest = $path.DIRECTORY_SEPARATOR.'SmsAliyun'.DIRECTORY_SEPARATOR.'plugin.json';
        if (! is_file($manifest)) {
            $this->markTestSkipped('SmsAliyun 插件目录不存在，跳过插件扫描断言。');
        }

        $decoded = json_decode((string) file_get_contents($manifest), true);
        $this->assertIsArray($decoded);
        $this->assertSame('sms-aliyun', $decoded['slug'] ?? null);
        $this->assertSame('sms', $decoded['type'] ?? null);
    }

    #[Test]
    public function themes_directory_contains_default_manifest(): void
    {
        $path = (string) config('starship.themes_path');
        $this->assertNotSame('', $path);
        $this->assertDirectoryExists($path);

        $manifest = $path.DIRECTORY_SEPARATOR.'default'.DIRECTORY_SEPARATOR.'theme.json';
        $this->assertFileExists($manifest);

        $decoded = json_decode((string) file_get_contents($manifest), true);
        $this->assertIsArray($decoded);
        $this->assertSame('default', $decoded['slug'] ?? null);
        $this->assertSame('frontend', $decoded['type'] ?? null);
        $this->assertSame('layouts.app', $decoded['entry']['layout'] ?? null);
    }

    #[Test]
    public function frontend_home_renders_current_theme(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('默认主题', false)
            ->assertSee('data-theme-slug', false)
            ->assertSee('default', false);
    }

    #[Test]
    public function core_contracts_are_bound(): void
    {
        $this->assertInstanceOf(SmsGateway::class, app(SmsSenderInterface::class));
        $this->assertInstanceOf(LocalStorage::class, app(StorageInterface::class));
    }
}
