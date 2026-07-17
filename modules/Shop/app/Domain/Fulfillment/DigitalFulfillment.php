<?php

namespace Modules\Shop\App\Domain\Fulfillment;

use Modules\Shop\App\Models\Order;
use Modules\Shop\App\Support\ShopFulfillmentType;

/**
 * 数字交付占位：权益开通 / 下载 / 订阅开通等。
 */
class DigitalFulfillment
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function fulfill(Order $order, array $context = []): array
    {
        $order->update([
            'fulfillment_type' => ShopFulfillmentType::DIGITAL,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
            'fulfillment_payload' => array_merge(
                $order->fulfillment_payload ?? [],
                [
                    'awaiting' => 'digital_grant',
                ],
            ),
        ]);

        return [
            'type' => ShopFulfillmentType::DIGITAL,
            'status' => Order::FULFILLMENT_PENDING,
            'message' => 'awaiting_digital_grant',
        ];
    }
}
