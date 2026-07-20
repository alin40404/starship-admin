<?php

namespace Modules\Shop\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Shop\App\Contracts\FulfillmentInterface;
use Modules\Shop\App\Contracts\InventoryInterface;
use Modules\Shop\App\Models\Order;
use Modules\Shop\App\Models\OrderItem;
use Modules\Shop\App\Models\Product;
use Modules\Shop\App\Models\ProductSku;
use Modules\Shop\App\Support\ShopFulfillmentType;
use Modules\Shop\App\Support\ShopProductStatus;
use RuntimeException;
use Starship\Core\Contracts\Payment\PaymentInterface;

class OrderService
{
    public function __construct(
        protected PaymentInterface $payment,
        protected InventoryInterface $inventory,
        protected FulfillmentInterface $fulfillment,
    ) {}

    public function createFromProduct(
        int $productId,
        int $quantity = 1,
        ?int $memberId = null,
        ?string $remark = null,
        ?int $skuId = null,
        array $fulfillmentPayload = [],
    ): Order {
        $product = Product::query()->whereKey($productId)->firstOrFail();

        if (! ShopProductStatus::isOnSale((int) $product->status)) {
            throw new RuntimeException(__('shop::messages.product_not_on_sale'));
        }

        $sku = $this->resolveSku($product, $skuId);
        if ((int) $sku->status !== 1) {
            throw new RuntimeException(__('shop::messages.product_not_on_sale'));
        }

        $quantity = max(1, $quantity);
        if ($this->inventory->availableSkuStock((int) $sku->id) < $quantity) {
            throw new RuntimeException(__('shop::messages.product_out_of_stock'));
        }

        return DB::transaction(function () use ($product, $sku, $quantity, $memberId, $remark, $fulfillmentPayload): Order {
            $amount = (int) $sku->price * $quantity;
            $order = Order::query()->create([
                'order_no' => $this->generateOrderNo(),
                'member_id' => $memberId,
                'product_id' => $product->id,
                'sku_id' => $sku->id,
                'product_name' => $product->name,
                'amount' => $amount,
                'quantity' => $quantity,
                'status' => Order::STATUS_PENDING,
                'remark' => $remark,
                'fulfillment_type' => $product->fulfillment_type
                    ?: ShopFulfillmentType::defaultForProductType($product->product_type),
                'fulfillment_payload' => $fulfillmentPayload ?: null,
                'fulfillment_status' => null,
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'sku_id' => $sku->id,
                'product_name' => $product->name,
                'sku_code' => $sku->sku_code,
                'specs' => $sku->specs,
                'price' => (int) $sku->price,
                'quantity' => $quantity,
                'amount' => $amount,
                'fulfillment_status' => null,
            ]);

            return $order->fresh(['items']);
        });
    }

    /**
     * @return array{order: Order, payment: array<string, mixed>}
     */
    public function prepay(Order $order, array $context = []): array
    {
        if (! $order->isPending()) {
            throw new RuntimeException(__('shop::messages.order_not_pending'));
        }

        $payment = $this->payment->prepay($order->order_no, array_merge([
            'amount' => (int) $order->amount,
            'subject' => $order->product_name,
            'notify_url' => $context['notify_url'] ?? url('/api/shop/payments/notify'),
        ], $context));

        $channel = (string) ($payment['provider'] ?? $payment['channel'] ?? 'unknown');
        $prepayId = isset($payment['prepay_id']) ? (string) $payment['prepay_id'] : null;

        $order->update([
            'pay_channel' => $channel,
            'prepay_id' => $prepayId,
        ]);

        return [
            'order' => $order->fresh(),
            'payment' => $payment,
        ];
    }

    public function syncPaymentStatus(Order $order): Order
    {
        if (! $order->isPending()) {
            return $order;
        }

        $result = $this->payment->query($order->order_no);

        return $this->applyPaymentResult($order, $result);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleNotify(string $orderNo, array $payload = []): Order
    {
        $order = Order::query()->where('order_no', $orderNo)->firstOrFail();

        if (! $order->isPending()) {
            return $order;
        }

        return $this->applyPaymentResult($order, $payload);
    }

    /**
     * @return array{order: Order, refund: array<string, mixed>}
     */
    public function refund(Order $order, ?string $reason = null): array
    {
        if (! $order->isPaid()) {
            throw new RuntimeException(__('shop::messages.order_not_paid'));
        }

        $refund = $this->payment->refund($order->order_no, [
            'amount' => (int) $order->amount,
            'reason' => $reason ?? __('shop::messages.refund_default_reason'),
        ]);

        $status = strtolower((string) ($refund['status'] ?? ''));
        if (! in_array($status, ['refunded', 'success'], true)) {
            throw new RuntimeException(__('shop::messages.refund_failed'));
        }

        $order = DB::transaction(function () use ($order): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isPaid()) {
                return $locked;
            }

            $locked->update([
                'status' => Order::STATUS_REFUNDED,
                'refunded_at' => now(),
            ]);

            $skuId = (int) ($locked->sku_id ?? 0);
            if ($skuId > 0) {
                $this->inventory->increaseSkuStock($skuId, (int) $locked->quantity);
            } else {
                Product::query()
                    ->whereKey($locked->product_id)
                    ->increment('stock', (int) $locked->quantity);
            }

            return $locked->fresh();
        });

        return [
            'order' => $order,
            'refund' => $refund,
        ];
    }

    public function cancel(Order $order): Order
    {
        if (! $order->isPending()) {
            throw new RuntimeException(__('shop::messages.order_not_pending'));
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return $order->fresh();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function applyPaymentResult(Order $order, array $result): Order
    {
        $status = strtolower((string) ($result['status'] ?? ''));
        $tradeState = strtoupper((string) ($result['trade_state'] ?? ''));
        $paid = in_array($status, ['paid', 'success'], true) || $tradeState === 'SUCCESS';

        if (! $paid) {
            return $order->fresh();
        }

        return $this->markPaid(
            $order,
            isset($result['trade_no']) ? (string) $result['trade_no'] : null,
            isset($result['provider']) ? (string) $result['provider'] : null,
        );
    }

    protected function markPaid(Order $order, ?string $tradeNo = null, ?string $channel = null): Order
    {
        $paid = DB::transaction(function () use ($order, $tradeNo, $channel): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isPending()) {
                return $locked;
            }

            $skuId = (int) ($locked->sku_id ?? 0);
            if ($skuId > 0) {
                $this->inventory->decreaseSkuStock($skuId, (int) $locked->quantity);
            } else {
                $product = Product::query()->whereKey($locked->product_id)->lockForUpdate()->firstOrFail();
                if ((int) $product->stock < (int) $locked->quantity) {
                    throw new RuntimeException(__('shop::messages.product_out_of_stock'));
                }
                $product->decrement('stock', (int) $locked->quantity);
            }

            $payload = [
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
            ];
            if ($tradeNo !== null && $tradeNo !== '') {
                $payload['trade_no'] = $tradeNo;
            }
            if ($channel !== null && $channel !== '') {
                $payload['pay_channel'] = $channel;
            }

            $locked->update($payload);

            return $locked->fresh();
        });

        // 支付成功后触发履约（事务外，避免履约失败回滚已支付）
        try {
            $this->fulfillment->fulfill($paid->order_no, [
                'fulfillment_type' => $paid->fulfillment_type,
            ]);
        } catch (\Throwable) {
            // 履约失败不阻断支付成功；状态可后续重试
            Order::query()->whereKey($paid->id)->update([
                'fulfillment_status' => Order::FULFILLMENT_FAILED,
            ]);
        }

        return $paid->fresh();
    }

    protected function resolveSku(Product $product, ?int $skuId): ProductSku
    {
        if ($skuId !== null && $skuId > 0) {
            return ProductSku::query()
                ->where('product_id', $product->id)
                ->whereKey($skuId)
                ->firstOrFail();
        }

        $sku = $product->defaultSku();
        if ($sku === null) {
            throw new RuntimeException(__('shop::messages.product_sku_missing'));
        }

        return $sku;
    }

    protected function generateOrderNo(): string
    {
        do {
            $orderNo = 'S'.now()->format('YmdHis').Str::upper(Str::random(6));
        } while (Order::query()->where('order_no', $orderNo)->exists());

        return $orderNo;
    }
}
