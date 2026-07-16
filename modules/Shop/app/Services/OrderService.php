<?php

namespace Modules\Shop\App\Services;

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
        ], $context));

        $channel = (string) ($payment['channel'] ?? $payment['mode'] ?? 'unknown');
        $order->update(['pay_channel' => $channel]);

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
        $status = (string) ($result['status'] ?? '');

        if (in_array($status, ['paid', 'success', 'SUCCESS'], true)) {
            $order->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
            ]);

            Product::query()
                ->whereKey($order->product_id)
                ->where('stock', '>=', $order->quantity)
                ->decrement('stock', $order->quantity);
        }

        return $order->fresh();
    }

    public function cancel(Order $order): Order
    {
        if (! $order->isPending()) {
            throw new RuntimeException(__('shop::messages.order_not_pending'));
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return $order->fresh();
    }

    protected function generateOrderNo(): string
    {
        do {
            $orderNo = 'S'.now()->format('YmdHis').Str::upper(Str::random(6));
        } while (Order::query()->where('order_no', $orderNo)->exists());

        return $orderNo;
    }
}
