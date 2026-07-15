<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Plugin\PluginPackageBuilder;
use Starship\Core\Tests\TestCase;

class PluginPackageBuilderTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir((string) config('starship.plugins_path').DIRECTORY_SEPARATOR.'MailSmtp')) {
            $this->markTestSkipped('MailSmtp 插件不存在');
        }

        $this->outputDir = storage_path('framework/testing/plugin-packs-'.uniqid('', true));
        File::ensureDirectoryExists($this->outputDir);
    }

    protected function tearDown(): void
    {
        if (isset($this->outputDir) && File::isDirectory($this->outputDir)) {
            File::deleteDirectory($this->outputDir);
        }

        parent::tearDown();
    }

    #[Test]
    public function export_writes_plugin_and_admin_backend(): void
    {
        $plugin = $this->app->make(PluginManager::class)->findBySlug('mail-smtp');
        $this->assertNotNull($plugin);

        $result = $this->app->make(PluginPackageBuilder::class)->export(
            $plugin,
            $this->outputDir,
            asZip: false,
        );

        $root = $result['package_root'];
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'manifest.json');
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'plugin'.DIRECTORY_SEPARATOR.'plugin.json');
        $this->assertFileExists(
            $root.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'panel.ts',
        );
        $this->assertDirectoryExists(
            $root.DIRECTORY_SEPARATOR.'plugin'.DIRECTORY_SEPARATOR.'frontend',
        );

        $manifest = json_decode((string) File::get($root.DIRECTORY_SEPARATOR.'manifest.json'), true);
        $this->assertIsArray($manifest);
        $this->assertSame('mail-smtp', $manifest['slug'] ?? null);
        $this->assertSame('plugins/MailSmtp', $manifest['plugin_target'] ?? null);
        $this->assertSame(
            'apps/admin/web-antd/src/plugins/mail-smtp',
            $manifest['backend_target'] ?? null,
        );
        $this->assertTrue((bool) ($manifest['has_backend'] ?? false));
        $this->assertTrue((bool) ($manifest['has_frontend'] ?? false));
    }
}
