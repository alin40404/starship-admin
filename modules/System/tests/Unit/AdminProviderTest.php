<?php

namespace Modules\System\Tests\Unit;

use Modules\System\App\Support\AdminProvider;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Admin\AdminProviderInterface;
use Tests\TestCase;

class AdminProviderTest extends TestCase
{
    #[Test]
    public function container_resolves_admin_provider_interface(): void
    {
        $provider = $this->app->make(AdminProviderInterface::class);

        $this->assertInstanceOf(AdminProvider::class, $provider);
    }
}
