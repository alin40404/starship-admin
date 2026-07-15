<?php

namespace Modules\System\Tests\Unit;

use Modules\System\App\Support\Mail\MailGateway;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Mail\MailSenderInterface;
use Tests\TestCase;

class MailGatewayTest extends TestCase
{
    #[Test]
    public function container_resolves_mail_sender_interface_to_mail_gateway(): void
    {
        $mailSender = $this->app->make(MailSenderInterface::class);

        $this->assertInstanceOf(MailGateway::class, $mailSender);
    }
}
