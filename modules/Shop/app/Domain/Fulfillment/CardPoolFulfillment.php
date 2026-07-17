<?php

namespace Modules\Shop\App\Domain\Fulfillment;

use Illuminate\Support\Facades\DB;
use Modules\Shop\App\Models\CardCode;
use Modules\Shop\App\Models\Order;
use Modules\Shop\App\Models\OrderItem;
use Modules\Shop\App\Support\ShopFulfillmentType;

/**
 * 卡券 / 兑换码履约：从 shop_card_codes 出库。
 */
class CardPoolFulfillment
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function fulfill(Order $order, array $context = [], ?string $poolType = null): array
    {
        $type = ShopFulfillmentType::usesCardPool($poolType)
            ? (string) $poolType
            : ShopFulfillmentType::CARD;

        return DB::transaction(function () use ($order, $context, $type): array {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $skuId = (int) ($locked->sku_id ?? 0);
            $codes = [];

            if ($skuId > 0) {
                $needed = max(1, (int) $locked->quantity);
                $available = CardCode::query()
                    ->where('sku_id', $skuId)
                    ->where('status', CardCode::STATUS_AVAILABLE)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->limit($needed)
                    ->get();

                if ($available->count() >= $needed) {
                    $item = OrderItem::query()->where('order_id', $locked->id)->first();
                    foreach ($available as $card) {
                        $card->update([
                            'status' => CardCode::STATUS_SOLD,
                            'order_id' => $locked->id,
                            'order_item_id' => $item?->id,
                            'sold_at' => now(),
                        ]);
                        $codes[] = $card->code;
                    }

                    $payload = array_merge($locked->fulfillment_payload ?? [], [
                        'pool_type' => $type,
                        'codes' => $codes,
                    ]);

                    $locked->update([
                        'fulfillment_type' => $type,
                        'fulfillment_status' => Order::FULFILLMENT_FULFILLED,
                        'fulfillment_payload' => $payload,
                    ]);

                    if ($item) {
                        $item->update(['fulfillment_status' => Order::FULFILLMENT_FULFILLED]);
                    }

                    return [
                        'type' => $type,
                        'status' => Order::FULFILLMENT_FULFILLED,
                        'codes' => $codes,
                        'message' => 'codes_delivered',
                    ];
                }
            }

            $locked->update([
                'fulfillment_type' => $type,
                'fulfillment_status' => Order::FULFILLMENT_PENDING,
                'fulfillment_payload' => array_merge($locked->fulfillment_payload ?? [], [
                    'pool_type' => $type,
                    'awaiting' => 'card_pool',
                ]),
            ]);

            return [
                'type' => $type,
                'status' => Order::FULFILLMENT_PENDING,
                'message' => 'awaiting_card_pool',
            ];
        });
    }
}
