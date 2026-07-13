<?php

namespace Starship\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Sms\SmsSenderInterface;
use Starship\Core\Tests\TestCase;

class SmsSenderInterfaceTest extends TestCase
{
    #[Test]
    public function sms_sender_interface_is_available(): void
    {
        $this->assertTrue(interface_exists(SmsSenderInterface::class));
        $this->assertTrue(method_exists(SmsSenderInterface::class, 'sendVerificationCode'));
    }
}
