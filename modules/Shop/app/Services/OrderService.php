<?php

namespace Modules\Shop\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Shop\App\Models\Order;
use Modules\Shop\App\Models\Product;
use RuntimeException;
use Starship\Core\Contracts\Payment\PaymentInterface;

class OrderService
{
    public function __construct(
        protected PaymentInterface $payment,
    ) {}

    public function createFromProduct(int $productId, int $quantity = 1, ?int $memberId = null, ?string $remark = null): Order
    {
        $product = Product::query()->whereKey($productId)->firstOrFail();

        if ((int) $product->status !== 1) {
            throw new RuntimeException(__('shop::messages.product_not_on_sale'));
        }

        $quantity = max(1, $quantity);
        if ((int) $product->stock < $quantity) {
            throw new RuntimeException(__('shop::messages.product_out_of_stock'));
        }

        return Order::query()->create([
            'order_no' => $this->generateOrderNo(),
            'member_id' => $memberId,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'amount' => (int) $product->price * $quantity,
            'quantity' => $quantity,
            'status' => Order::STATUS_PENDING,
            'remark' => $remark,
        ]);
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
     * 支付渠道异步回调：按订单号落库，幂等。
     *
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

            Product::query()
                ->whereKey($locked->product_id)
                ->increment('stock', (int) $locked->quantity);

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
        return DB::transaction(function () use ($order, $tradeNo, $channel): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isPending()) {
                return $locked;
            }

            $product = Product::query()->whereKey($locked->product_id)->lockForUpdate()->firstOrFail();
            if ((int) $product->stock < (int) $locked->quantity) {
                throw new RuntimeException(__('shop::messages.product_out_of_stock'));
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
            $product->decrement('stock', (int) $locked->quantity);

            return $locked->fresh();
        });
    }

    protected function generateOrderNo(): string
    {
        do {
            $orderNo = 'S'.now()->format('YmdHis').Str::upper(Str::random(6));
        } while (Order::query()->where('order_no', $orderNo)->exists());

        return $orderNo;
    }
}
