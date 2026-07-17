<?php

namespace Modules\Shop\App\Domain\Fulfillment;

use Modules\Shop\App\Models\Order;
use Modules\Shop\App\Support\ShopFulfillmentType;

/**
 * 实物物流履约占位：支付成功后进入待发货。
 */
class PhysicalFulfillment
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function fulfill(Order $order, array $context = []): array
    {
        $order->update([
            'fulfillment_type' => ShopFulfillmentType::SHIPPING,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
            'fulfillment_payload' => array_merge(
                $order->fulfillment_payload ?? [],
                array_filter([
                    'address' => $context['address'] ?? null,
                ]),
            ),
        ]);

        return [
            'type' => ShopFulfillmentType::SHIPPING,
            'status' => Order::FULFILLMENT_PENDING,
            'message' => 'pending_shipment',
        ];
    }
}
