<?php

namespace Modules\Shop\App\Contracts;

/**
 * 商城履约契约：支付成功后完成订单交付（物流 / 卡密 / 数字发放等）。
 * 属于 Shop 业务能力，不进入 Core。
 */
interface FulfillmentInterface
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function fulfill(string $orderNo, array $context = []): array;
}
