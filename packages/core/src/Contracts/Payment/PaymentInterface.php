<?php

namespace Starship\Core\Contracts\Payment;

/**
 * 支付契约。接口已入库；System 默认实现与插件覆盖延后（待 Shop / 订单业务）。
 */
interface PaymentInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createOrder(array $data): array;

    /**
     * @return array<string, mixed>
     */
    public function pay(string $orderNo): array;

    /**
     * @return array<string, mixed>
     */
    public function refund(string $orderNo): array;

    /**
     * @return array<string, mixed>
     */
    public function query(string $orderNo): array;
}
