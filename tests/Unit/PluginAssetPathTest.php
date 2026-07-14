<?php

namespace Starship\Core\Tests\Unit;

use Starship\Core\Plugin\PluginManager;
use Starship\Core\Tests\TestCase;

class PluginAssetPathTest extends TestCase
{
    public function test_resolves_assets_under_plugin_and_blocks_traversal(): void
    {
        $plugin = $this->app->make(PluginManager::class)->findBySlug('sms-aliyun');
        $this->assertNotNull($plugin);

        $svg = $plugin->resolveAssetPath('images/demo.svg');
        $this->assertNotNull($svg);
        $this->assertFileExists($svg);

        $this->assertNull($plugin->resolveAssetPath('../plugin.json'));
        $this->assertNull($plugin->resolveAssetPath('images/../../plugin.json'));
        $this->assertNull($plugin->resolveAssetPath('missing/file.css'));
    }

    public function test_plugin_asset_helper_points_to_plugin_assets_route(): void
    {
        $url = plugin_asset('sms-aliyun', 'images/demo.svg');
        $this->assertStringContainsString('/plugins/sms-aliyun/assets/images/demo.svg', $url);
    }

    public function test_plugin_asset_respects_cdn_base(): void
    {
        config(['starship.asset_url' => 'https://cdn.example.com/static']);

        $url = plugin_asset('sms-aliyun', 'images/demo.svg');
        $this->assertStringStartsWith('https://cdn.example.com/static/plugins/sms-aliyun/assets/images/demo.svg', $url);
    }
}
