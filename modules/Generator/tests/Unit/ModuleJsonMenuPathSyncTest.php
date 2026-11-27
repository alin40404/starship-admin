<?php

namespace Modules\Generator\Tests\Unit;

use Illuminate\Support\Facades\File;
use Modules\Generator\App\Services\ModuleJsonMenuPathSync;
use Modules\Generator\App\Support\ModulePathResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModuleJsonMenuPathSyncTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'starship-module-json-'.uniqid('', true);

        File::ensureDirectoryExists($this->tempRoot.DIRECTORY_SEPARATOR.'Demo');
        File::put(
            $this->tempRoot.DIRECTORY_SEPARATOR.'Demo'.DIRECTORY_SEPARATOR.'module.json',
            json_encode([
                'name' => 'demo',
                'title' => 'Demo',
                'enabled' => true,
                'providers' => ['Modules\\Demo\\App\\Providers\\DemoServiceProvider'],
                'menus' => [
                    'paths' => ['/dev/codegen'],
                ],
                'permissions' => [
                    'prefixes' => ['demo'],
                    'exact' => [],
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->tempRoot) && File::isDirectory($this->tempRoot)) {
            File::deleteDirectory($this->tempRoot);
        }

        parent::tearDown();
    }

    #[Test]
    public function add_appends_exact_path_without_touching_others(): void
    {
        $sync = $this->makeSync();

        $this->assertSame('added', $sync->add('demo', '/generator/products'));
        $this->assertSame('exists', $sync->add('demo', '/generator/products'));

        $manifest = $this->readManifest();
        $this->assertSame(['/dev/codegen', '/generator/products'], $manifest['menus']['paths']);
        $this->assertSame('demo', $manifest['name']);
        $this->assertSame(['demo'], $manifest['permissions']['prefixes']);
    }

    #[Test]
    public function remove_only_deletes_exact_path(): void
    {
        $sync = $this->makeSync();
        $sync->add('demo', '/generator/products');
        $sync->add('demo', '/generator/orders');

        $this->assertSame('removed', $sync->remove('demo', '/generator/products'));
        $this->assertSame('missing', $sync->remove('demo', '/generator/products'));

        $manifest = $this->readManifest();
        $this->assertSame(['/dev/codegen', '/generator/orders'], $manifest['menus']['paths']);
    }

    #[Test]
    public function empty_path_is_skipped(): void
    {
        $sync = $this->makeSync();

        $this->assertSame('skipped', $sync->add('demo', ''));
        $this->assertSame('skipped', $sync->remove('demo', null));
        $this->assertSame(['/dev/codegen'], $this->readManifest()['menus']['paths']);
    }

    private function makeSync(): ModuleJsonMenuPathSync
    {
        return new ModuleJsonMenuPathSync(new ModulePathResolver($this->tempRoot));
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(): array
    {
        $decoded = json_decode(
            (string) File::get($this->tempRoot.DIRECTORY_SEPARATOR.'Demo'.DIRECTORY_SEPARATOR.'module.json'),
            true,
        );

        $this->assertIsArray($decoded);

        return $decoded;
    }
}
