<?php

namespace Starship\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Mail\MailSenderInterface;
use Starship\Core\Tests\TestCase;

class MailSenderInterfaceTest extends TestCase
{
    #[Test]
    public function mail_sender_interface_is_available(): void
    {
        $this->assertTrue(interface_exists(MailSenderInterface::class));
        $this->assertTrue(method_exists(MailSenderInterface::class, 'send'));
    }
}
