<?php

namespace Starship\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Config\ConfigRepositoryInterface;
use Starship\Core\Contracts\Mail\MailSenderInterface;
use Starship\Core\Contracts\Notification\NotificationInterface;
use Starship\Core\Contracts\Payment\PaymentInterface;
use Starship\Core\Contracts\Permission\PermissionCheckerInterface;
use Starship\Core\Contracts\Sms\SmsSenderInterface;
use Starship\Core\Contracts\Storage\StorageInterface;
use Starship\Core\Contracts\Admin\AdminProviderInterface;
use Starship\Core\Contracts\Member\MemberProviderInterface;
use Starship\Core\Tests\TestCase;

class ContractsCatalogTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: list<string>}>
     */
    public static function contractCatalog(): array
    {
        return [
            'sms' => [SmsSenderInterface::class, ['sendVerificationCode']],
            'mail' => [MailSenderInterface::class, ['send']],
            'storage' => [StorageInterface::class, ['put', 'get', 'delete', 'url', 'exists']],
            'payment' => [PaymentInterface::class, ['prepay', 'refund', 'query']],
            'notification' => [NotificationInterface::class, ['send']],
            'admin' => [AdminProviderInterface::class, ['find', 'current']],
            'member' => [MemberProviderInterface::class, ['find', 'current']],
            'config' => [ConfigRepositoryInterface::class, ['get', 'set', 'setMany']],
            'permission' => [PermissionCheckerInterface::class, ['check']],
        ];
    }

    #[Test]
    #[DataProvider('contractCatalog')]
    public function contract_interface_is_catalogued(string $interface, array $methods): void
    {
        $this->assertTrue(interface_exists($interface), "missing interface {$interface}");

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($interface, $method),
                "{$interface}::{$method} missing",
            );
        }
    }
}
