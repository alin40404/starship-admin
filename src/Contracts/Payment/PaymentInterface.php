<?php

namespace Starship\Core\Contracts\Payment;

/**
 * 支付通道契约（插件实现）。
 *
 * 业务订单由 Shop 等 Module 创建并持久化；本接口只负责向微信/支付宝等通道
 * 发起预支付、退款与查单。orderNo 一律指业务侧已存在的订单号。
 */
interface PaymentInterface
{
    /**
     * 向支付通道发起预支付（统一下单），返回 prepay_id / 客户端调起参数等。
     *
     * @param  array{amount: int, subject?: string, notify_url?: string, ...}  $context
     * @return array<string, mixed>
     */
    public function prepay(string $orderNo, array $context = []): array;

    /**
     * @param  array{amount?: int, reason?: string, ...}  $context
     * @return array<string, mixed>
     */
    public function refund(string $orderNo, array $context = []): array;

    /**
     * @return array<string, mixed>
     */
    public function query(string $orderNo): array;
}
