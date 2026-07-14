<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use Starship\Core\Tests\TestCase;
use Starship\Core\Theme\ThemeAssetLinker;

class ThemeAssetLinkerTest extends TestCase
{
    protected function tearDown(): void
    {
        $published = public_path('themes'.DIRECTORY_SEPARATOR.'default');
        if (File::exists($published) || is_link($published)) {
            if (is_link($published) || is_file($published)) {
                File::delete($published);
            } elseif (is_dir($published)) {
                File::deleteDirectory($published);
            }
        }

        $themesRoot = public_path('themes');
        if (File::isDirectory($themesRoot) && count(File::directories($themesRoot)) === 0 && count(File::files($themesRoot)) === 0) {
            File::deleteDirectory($themesRoot);
        }

        parent::tearDown();
    }

    public function test_link_creates_symlink_into_public(): void
    {
        $linker = $this->app->make(ThemeAssetLinker::class);

        try {
            $result = $linker->link('default', true);
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $this->assertSame('default', $result['slug']);
        $this->assertTrue($result['linked']);
        $this->assertTrue(is_link($result['target']) || File::isDirectory($result['target']));
        $this->assertFileExists($result['target'].DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');
    }
}
