<?php

namespace Modules\System\Tests\Unit;

use Modules\System\App\Support\Payment\PaymentGateway;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Payment\PaymentInterface;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    #[Test]
    public function container_resolves_payment_interface_to_payment_gateway(): void
    {
        $payment = $this->app->make(PaymentInterface::class);

        $this->assertInstanceOf(PaymentGateway::class, $payment);
    }
}
