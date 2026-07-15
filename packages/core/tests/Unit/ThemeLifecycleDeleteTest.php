<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Tests\TestCase;
use Starship\Core\Theme\ThemeLifecycle;
use Starship\Core\Theme\ThemeManager;

class ThemeLifecycleDeleteTest extends TestCase
{
    private string $slug;

    private string $themePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slug = 'tmp-delete-'.substr(uniqid('', true), -6);
        $this->themePath = rtrim((string) config('starship.themes_path'), '/\\')
            .DIRECTORY_SEPARATOR.$this->slug;

        File::ensureDirectoryExists($this->themePath);
        File::put(
            $this->themePath.DIRECTORY_SEPARATOR.'theme.json',
            json_encode([
                'name' => 'TmpDelete',
                'slug' => $this->slug,
                'title' => 'Tmp Delete',
                'version' => '1.0.0',
                'type' => 'frontend',
                'require' => ['modules' => []],
                'entry' => ['layout' => 'layouts.app', 'home' => 'pages.home'],
            ], JSON_UNESCAPED_UNICODE),
        );
        File::ensureDirectoryExists($this->themePath.DIRECTORY_SEPARATOR.'views');
    }

    protected function tearDown(): void
    {
        if (isset($this->themePath) && File::isDirectory($this->themePath)) {
            File::deleteDirectory($this->themePath);
        }

        parent::tearDown();
    }

    #[Test]
    public function delete_removes_directory(): void
    {
        $this->assertNotNull($this->app->make(ThemeManager::class)->findBySlug($this->slug));

        $this->app->make(ThemeLifecycle::class)->delete($this->slug);

        $this->assertDirectoryDoesNotExist($this->themePath);
        $this->assertNull($this->app->make(ThemeManager::class)->findBySlug($this->slug));
    }

    #[Test]
    public function delete_refuses_protected_default(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('受保护主题不可删除');

        $this->app->make(ThemeLifecycle::class)->delete('default');
    }
}
