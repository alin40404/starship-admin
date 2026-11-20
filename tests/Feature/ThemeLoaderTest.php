<?php

namespace Starship\Core\Tests\Feature;

use Illuminate\Support\Facades\View;
use Starship\Core\Tests\TestCase;
use Starship\Core\Theme\ThemeLoader;

class ThemeLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetActiveThemesToDefault();
        $this->registerThemeViewRoutes();
    }

    public function test_registers_theme_namespace_with_default_fallback(): void
    {
        $loader = $this->app->make(ThemeLoader::class);
        $loader->register('frontend');

        $this->assertTrue($loader->registered());
        $this->assertNotNull($loader->theme());
        $this->assertSame('default', $loader->theme()?->slug());
        $this->assertTrue(View::exists('theme::pages.home'));
        $this->assertTrue(View::exists('theme::layouts.app'));
        $this->assertSame('theme::pages.home', $loader->view('home'));
    }

    public function test_home_view_renders_theme_title(): void
    {
        $loader = $this->app->make(ThemeLoader::class);
        $loader->register('frontend');

        $html = view($loader->view('home'))->render();

        $this->assertStringContainsString('默认主题', $html);
        $this->assertStringContainsString('data-theme-slug', $html);
        $this->assertStringContainsString('default', $html);
        $this->assertStringContainsString('/themes/default/assets/css/app.css', $html);
    }
}
