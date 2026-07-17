<?php

namespace Modules\Shop\App\Domain\Fulfillment;

use Modules\Shop\App\Contracts\FulfillmentInterface;
use Modules\Shop\App\Models\Order;
use Modules\Shop\App\Support\ShopFulfillmentType;

/**
 * 普通商城履约：物流 / 数字发放 / 卡密兑换码 / 人工。
 */
class FulfillmentDispatcher implements FulfillmentInterface
{
    public function __construct(
        protected PhysicalFulfillment $shipping,
        protected DigitalFulfillment $digital,
        protected CardPoolFulfillment $cardPool,
        protected ManualFulfillment $manual,
    ) {}

    public function fulfill(string $orderNo, array $context = []): array
    {
        $order = Order::query()->where('order_no', $orderNo)->firstOrFail();
        $type = (string) ($order->fulfillment_type
            ?: $context['fulfillment_type']
            ?? ShopFulfillmentType::SHIPPING);

        return match ($type) {
            ShopFulfillmentType::DIGITAL => $this->digital->fulfill($order, $context),
            ShopFulfillmentType::CARD, ShopFulfillmentType::CODE => $this->cardPool->fulfill($order, $context, $type),
            ShopFulfillmentType::MANUAL => $this->manual->fulfill($order, $context),
            default => $this->shipping->fulfill($order, $context),
        };
    }
}
