<?php

namespace Starship\Core\Tests\Feature;

use Illuminate\Support\Facades\File;
use Starship\Core\Tests\TestCase;

class PluginMakeCommandTest extends TestCase
{
    private string $pluginsPath = '';

    protected function tearDown(): void
    {
        if ($this->pluginsPath !== '' && File::isDirectory($this->pluginsPath)) {
            File::deleteDirectory($this->pluginsPath);
        }

        parent::tearDown();
    }

    public function test_plugin_make_scaffolds_plugin_files(): void
    {
        $this->pluginsPath = storage_path('framework/testing/plugins-'.uniqid('', true));
        config(['starship.plugins_path' => $this->pluginsPath]);

        $this->artisan('starship:plugin:make', [
            'name' => 'TestSms',
            '--type' => 'sms',
            '--slug' => 'test-sms',
            '--description' => '测试短信插件',
        ])->assertSuccessful();

        $pluginPath = $this->pluginsPath.DIRECTORY_SEPARATOR.'TestSms';

        $this->assertFileExists($pluginPath.DIRECTORY_SEPARATOR.'plugin.json');
        $this->assertFileExists($pluginPath.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'test-sms.php');
        $this->assertFileExists($pluginPath.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'admin-api.php');
        $this->assertFileExists(
            $pluginPath.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR.'TestSmsServiceProvider.php',
        );

        $manifest = json_decode(
            (string) file_get_contents($pluginPath.DIRECTORY_SEPARATOR.'plugin.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame('TestSms', $manifest['name']);
        $this->assertSame('test-sms', $manifest['slug']);
        $this->assertSame('sms', $manifest['type']);
        $this->assertSame('Plugins\\TestSms\\Providers\\TestSmsServiceProvider', $manifest['providers'][0]);
    }

    public function test_plugin_make_rejects_invalid_type(): void
    {
        $this->pluginsPath = storage_path('framework/testing/plugins-'.uniqid('', true));
        config(['starship.plugins_path' => $this->pluginsPath]);

        $this->artisan('starship:plugin:make', [
            'name' => 'BadPlugin',
            '--type' => 'invalid',
        ])->assertFailed();
    }
}
