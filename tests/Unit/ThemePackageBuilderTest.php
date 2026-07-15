<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Tests\TestCase;
use Starship\Core\Theme\ThemeManager;
use Starship\Core\Theme\ThemePackageBuilder;

class ThemePackageBuilderTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir((string) config('starship.themes_path').DIRECTORY_SEPARATOR.'default')) {
            $this->markTestSkipped('default 主题不存在');
        }

        $this->outputDir = storage_path('framework/testing/theme-packs-'.uniqid('', true));
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
    public function export_writes_theme_payload(): void
    {
        $theme = $this->app->make(ThemeManager::class)->findBySlug('default');
        $this->assertNotNull($theme);

        $result = $this->app->make(ThemePackageBuilder::class)->export(
            $theme,
            $this->outputDir,
            asZip: false,
        );

        $root = $result['package_root'];
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'manifest.json');
        $this->assertFileExists($root.DIRECTORY_SEPARATOR.'theme'.DIRECTORY_SEPARATOR.'theme.json');
        $this->assertDirectoryExists(
            $root.DIRECTORY_SEPARATOR.'theme'.DIRECTORY_SEPARATOR.'views',
        );

        $manifest = json_decode((string) File::get($root.DIRECTORY_SEPARATOR.'manifest.json'), true);
        $this->assertIsArray($manifest);
        $this->assertSame('theme', $manifest['type'] ?? null);
        $this->assertSame('default', $manifest['slug'] ?? null);
        $this->assertSame('themes/default', $manifest['theme_target'] ?? null);
    }

    #[Test]
    public function import_round_trip_to_new_slug_target(): void
    {
        $theme = $this->app->make(ThemeManager::class)->findBySlug('default');
        $this->assertNotNull($theme);

        $builder = $this->app->make(ThemePackageBuilder::class);
        $exported = $builder->export($theme, $this->outputDir, asZip: false);
        $root = $exported['package_root'];

        $manifest = json_decode((string) File::get($root.DIRECTORY_SEPARATOR.'manifest.json'), true);
        $this->assertIsArray($manifest);

        $importSlug = 'default-pack-test-'.substr(uniqid('', true), -6);
        $manifest['slug'] = $importSlug;
        $manifest['theme_target'] = 'themes/'.$importSlug;
        File::put(
            $root.DIRECTORY_SEPARATOR.'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n",
        );

        $targetAbsolute = rtrim((string) config('starship.repo_root'), '/\\')
            .DIRECTORY_SEPARATOR.'themes'
            .DIRECTORY_SEPARATOR.$importSlug;

        try {
            $builder->import($root, $manifest, force: false);
            $this->assertFileExists($targetAbsolute.DIRECTORY_SEPARATOR.'theme.json');
            $this->assertDirectoryExists($targetAbsolute.DIRECTORY_SEPARATOR.'views');
        } finally {
            if (File::isDirectory($targetAbsolute)) {
                File::deleteDirectory($targetAbsolute);
            }
        }
    }
}
