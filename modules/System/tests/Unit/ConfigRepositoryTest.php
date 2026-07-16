<?php

namespace Modules\System\Tests\Unit;

use Modules\System\App\Support\Config\ConfigRepository;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Config\ConfigRepositoryInterface;
use Tests\TestCase;

class ConfigRepositoryTest extends TestCase
{
    #[Test]
    public function container_resolves_config_repository_interface(): void
    {
        $repo = $this->app->make(ConfigRepositoryInterface::class);

        $this->assertInstanceOf(ConfigRepository::class, $repo);
    }
}
