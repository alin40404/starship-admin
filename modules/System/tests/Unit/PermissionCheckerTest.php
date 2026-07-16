<?php

namespace Modules\System\Tests\Unit;

use Modules\System\App\Support\Permission\PermissionChecker;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Permission\PermissionCheckerInterface;
use Tests\TestCase;

class PermissionCheckerTest extends TestCase
{
    #[Test]
    public function container_resolves_permission_checker_interface(): void
    {
        $checker = $this->app->make(PermissionCheckerInterface::class);

        $this->assertInstanceOf(PermissionChecker::class, $checker);
    }
}
