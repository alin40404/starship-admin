<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Module\ModuleComposerLinker;
use Starship\Core\Tests\TestCase;

class ModuleComposerLinkerTest extends TestCase
{
    private string $root = '';

    protected function tearDown(): void
    {
        if ($this->root !== '' && File::isDirectory($this->root)) {
            File::deleteDirectory($this->root);
        }

        parent::tearDown();
    }

    #[Test]
    public function link_adds_path_repository_and_require(): void
    {
        $this->root = storage_path('framework/testing/composer-link-'.uniqid('', true));
        $backend = $this->root.DIRECTORY_SEPARATOR.'backend';
        $module = $this->root.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'Shop';
        File::ensureDirectoryExists($backend);
        File::ensureDirectoryExists($module);

        File::put($backend.DIRECTORY_SEPARATOR.'composer.json', <<<'JSON'
{
    "name": "laravel/laravel",
    "require": {
        "php": "^8.3",
        "starship-admin/core": "@dev"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../packages/core"
        }
    ]
}
JSON);

        File::put($module.DIRECTORY_SEPARATOR.'composer.json', <<<'JSON'
{
    "name": "starship/shop",
    "type": "library"
}
JSON);

        $linker = new ModuleComposerLinker;
        $result = $linker->link(
            $backend.DIRECTORY_SEPARATOR.'composer.json',
            $module,
        );

        $this->assertSame('starship/shop', $result['package']);
        $this->assertSame('../modules/Shop', $result['path_url']);
        $this->assertTrue($result['repository_added']);
        $this->assertTrue($result['require_added']);
        $this->assertTrue($result['changed']);

        $composer = json_decode(
            (string) File::get($backend.DIRECTORY_SEPARATOR.'composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertSame('@dev', $composer['require']['starship/shop'] ?? null);
        $this->assertSame('@dev', $composer['require']['starship-admin/core'] ?? null);

        $urls = collect($composer['repositories'])
            ->pluck('url')
            ->all();
        $this->assertContains('../packages/core', $urls);
        $this->assertContains('../modules/Shop', $urls);

        $again = $linker->link(
            $backend.DIRECTORY_SEPARATOR.'composer.json',
            $module,
        );
        $this->assertFalse($again['changed']);
        $this->assertFalse($again['repository_added']);
        $this->assertFalse($again['require_added']);
    }
}
