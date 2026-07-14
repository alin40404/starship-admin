<?php

namespace Modules\System\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Modules\System\App\Support\Storage\LocalStorage;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Storage\StorageInterface;
use Tests\TestCase;

class LocalStorageTest extends TestCase
{
    #[Test]
    public function container_resolves_storage_interface_to_local_storage(): void
    {
        $storage = $this->app->make(StorageInterface::class);

        $this->assertInstanceOf(LocalStorage::class, $storage);
    }

    #[Test]
    public function local_storage_put_get_url_delete_roundtrip(): void
    {
        Storage::fake('public');

        $storage = (new LocalStorage('public'));
        $path = 'contracts/storage-smoke.txt';

        $this->assertTrue($storage->put($path, 'starship'));
        $this->assertTrue($storage->exists($path));
        $this->assertSame('starship', $storage->get($path));
        $this->assertNotSame('', $storage->url($path));
        $this->assertTrue($storage->delete($path));
        $this->assertFalse($storage->exists($path));
    }
}
