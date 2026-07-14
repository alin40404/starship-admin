<?php

namespace Starship\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Storage\StorageInterface;
use Starship\Core\Tests\TestCase;

class StorageInterfaceTest extends TestCase
{
    #[Test]
    public function storage_interface_is_available(): void
    {
        $this->assertTrue(interface_exists(StorageInterface::class));

        foreach (['put', 'get', 'delete', 'url', 'exists'] as $method) {
            $this->assertTrue(
                method_exists(StorageInterface::class, $method),
                "StorageInterface 缺少方法：{$method}",
            );
        }
    }
}
