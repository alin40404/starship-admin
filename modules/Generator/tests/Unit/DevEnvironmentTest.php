<?php

namespace Modules\Generator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Support\DevEnvironment;
use Tests\TestCase;

class DevEnvironmentTest extends TestCase
{
    #[Test]
    public function is_development_when_app_env_is_local(): void
    {
        $this->app->detectEnvironment(static fn () => 'local');

        $this->assertTrue(DevEnvironment::isDevelopment());
    }

    #[Test]
    public function is_development_when_app_env_is_development(): void
    {
        $this->app->detectEnvironment(static fn () => 'development');

        $this->assertTrue(DevEnvironment::isDevelopment());
    }

    #[Test]
    public function is_not_development_when_app_env_is_production(): void
    {
        $this->app->detectEnvironment(static fn () => 'production');

        $this->assertFalse(DevEnvironment::isDevelopment());
    }
}
