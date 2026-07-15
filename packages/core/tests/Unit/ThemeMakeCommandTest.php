<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Tests\TestCase;

class ThemeMakeCommandTest extends TestCase
{
    private string $themesRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themesRoot = storage_path('framework/testing/themes-make-'.uniqid('', true));
        File::ensureDirectoryExists($this->themesRoot);
        $this->app['config']->set('starship.themes_path', $this->themesRoot);
    }

    protected function tearDown(): void
    {
        if (isset($this->themesRoot) && File::isDirectory($this->themesRoot)) {
            File::deleteDirectory($this->themesRoot);
        }

        parent::tearDown();
    }

    #[Test]
    public function make_scaffolds_theme_tree(): void
    {
        $this->artisan('starship:theme:make', [
            'name' => 'Demo Landing',
            '--title' => '演示落地页',
        ])->assertSuccessful();

        $root = $this->themesRoot.DIRECTORY_SEPARATOR.'demo-landing';
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'theme.json');
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'home.blade.php');
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'app.css');

        $manifest = json_decode((string) File::get($root.DIRECTORY_SEPARATOR.'theme.json'), true);
        $this->assertIsArray($manifest);
        $this->assertSame('demo-landing', $manifest['slug'] ?? null);
        $this->assertSame('演示落地页', $manifest['title'] ?? null);
    }
}
