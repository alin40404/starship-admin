<?php

namespace Starship\Core\Tests\Unit;

use Starship\Core\Tests\TestCase;
use Starship\Core\Theme\ThemeManager;

class ThemeAssetPathTest extends TestCase
{
    public function test_resolves_assets_under_theme_and_blocks_traversal(): void
    {
        $theme = $this->app->make(ThemeManager::class)->findBySlug('default');
        $this->assertNotNull($theme);

        $css = $theme->resolveAssetPath('css/app.css');
        $this->assertNotNull($css);
        $this->assertFileExists($css);

        $this->assertNull($theme->resolveAssetPath('../theme.json'));
        $this->assertNull($theme->resolveAssetPath('css/../../theme.json'));
        $this->assertNull($theme->resolveAssetPath('missing/file.css'));
    }

    public function test_theme_asset_helper_points_to_theme_assets_route(): void
    {
        $url = theme_asset('css/app.css');
        $this->assertStringContainsString('/themes/default/assets/css/app.css', $url);
    }

    public function test_theme_asset_respects_cdn_base(): void
    {
        config(['starship.theme_asset_url' => 'https://cdn.example.com/static']);

        $url = theme_asset('css/app.css');
        $this->assertStringStartsWith('https://cdn.example.com/static/themes/default/assets/css/app.css', $url);
    }
}
