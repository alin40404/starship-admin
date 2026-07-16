<?php

namespace Modules\System\App\Support\Payment;

use RuntimeException;
use Starship\Core\Contracts\Payment\PaymentInterface;

/**
 * 默认支付网关：未启用支付插件时占位，提示安装并启用官方插件。
 */
class PaymentGateway implements PaymentInterface
{
    public function prepay(string $orderNo, array $context = []): array
    {
        throw new RuntimeException(__('system::messages.payment_plugin_required'));
    }

    public function refund(string $orderNo, array $context = []): array
    {
        throw new RuntimeException(__('system::messages.payment_plugin_required'));
    }

    public function query(string $orderNo): array
    {
        throw new RuntimeException(__('system::messages.payment_plugin_required'));
    }
}
