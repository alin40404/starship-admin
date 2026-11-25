<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Starship\Core\Install\InstallLock;
use Starship\Core\Tests\TestCase;

class InstallLockTest extends TestCase
{
    protected function tearDown(): void
    {
        $lock = new InstallLock;
        if (File::exists($lock->path())) {
            File::delete($lock->path());
        }

        parent::tearDown();
    }

    public function test_lock_roundtrip(): void
    {
        $lock = new InstallLock;
        $this->assertFalse($lock->isInstalled());

        $lock->lock();
        $this->assertTrue($lock->isInstalled());
        $this->assertFileExists($lock->path());

        $this->expectException(RuntimeException::class);
        $lock->assertNotInstalled();
    }
}
