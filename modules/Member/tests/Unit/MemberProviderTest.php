<?php

namespace Modules\Member\Tests\Unit;

use Modules\Member\App\Support\MemberProvider;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Member\MemberProviderInterface;
use Tests\TestCase;

class MemberProviderTest extends TestCase
{
    #[Test]
    public function container_resolves_member_provider_interface(): void
    {
        $provider = $this->app->make(MemberProviderInterface::class);

        $this->assertInstanceOf(MemberProvider::class, $provider);
    }
}
