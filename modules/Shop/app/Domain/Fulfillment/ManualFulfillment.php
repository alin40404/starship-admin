<?php

namespace Modules\Shop\App\Domain\Fulfillment;

use Modules\Shop\App\Models\Order;
use Modules\Shop\App\Support\ShopFulfillmentType;

/**
 * 人工履约：支付后待运营处理。
 */
class ManualFulfillment
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function fulfill(Order $order, array $context = []): array
    {
        $order->update([
            'fulfillment_type' => ShopFulfillmentType::MANUAL,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
            'fulfillment_payload' => array_merge($order->fulfillment_payload ?? [], [
                'awaiting' => 'manual_ops',
                'note' => $context['note'] ?? null,
            ]),
        ]);

        return [
            'type' => ShopFulfillmentType::MANUAL,
            'status' => Order::FULFILLMENT_PENDING,
            'message' => 'awaiting_manual',
        ];
    }
}
