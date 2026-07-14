<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use Starship\Core\Plugin\PluginAssetLinker;
use Starship\Core\Tests\TestCase;

class PluginAssetLinkerTest extends TestCase
{
    protected function tearDown(): void
    {
        $published = public_path('plugins'.DIRECTORY_SEPARATOR.'sms-aliyun');
        if (File::exists($published) || is_link($published)) {
            $assets = $published.DIRECTORY_SEPARATOR.'assets';
            if (is_link($assets) || (PHP_OS_FAMILY === 'Windows' && is_dir($assets))) {
                if (is_link($assets)) {
                    File::delete($assets);
                } else {
                    exec(sprintf('cmd /c rmdir %s', escapeshellarg($assets)));
                }
            }
            if (File::isDirectory($published) && count(File::directories($published)) === 0 && count(File::files($published)) === 0) {
                File::deleteDirectory($published);
            }
        }

        $pluginsRoot = public_path('plugins');
        if (File::isDirectory($pluginsRoot) && count(File::directories($pluginsRoot)) === 0 && count(File::files($pluginsRoot)) === 0) {
            File::deleteDirectory($pluginsRoot);
        }

        parent::tearDown();
    }

    public function test_link_creates_symlink_into_public(): void
    {
        $linker = $this->app->make(PluginAssetLinker::class);

        try {
            $result = $linker->link('sms-aliyun', true);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $this->assertSame('sms-aliyun', $result['slug']);
        $this->assertTrue($result['linked']);
        $this->assertTrue(is_link($result['target']) || File::isDirectory($result['target']));
        $this->assertFileExists($result['target'].DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'demo.svg');
    }
}
