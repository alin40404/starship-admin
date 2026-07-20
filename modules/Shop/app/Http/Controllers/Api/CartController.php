<?php

namespace Modules\Shop\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Shop\App\Models\Cart;
use Modules\Shop\App\Models\CartItem;
use Modules\Shop\App\Models\Product;
use Modules\Shop\App\Models\ProductSku;
use Modules\Shop\App\Services\OrderService;
use Modules\Shop\App\Support\ShopProductStatus;
use RuntimeException;
use Starship\Core\Support\ApiResponse;

/**
 * 前台购物车最小 API（会员鉴权由调用方保证；此处用 member_id 入参做切片）。
 */
class CartController extends Controller
{
    public function __construct(
        protected OrderService $orders,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $memberId = (int) $request->validate(['member_id' => ['required', 'integer', 'min:1']])['member_id'];
        $cart = $this->cartForMember($memberId);

        return ApiResponse::success($cart->load(['items.product', 'items.sku']));
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer', 'min:1'],
            'sku_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $sku = ProductSku::query()->whereKey($data['sku_id'])->firstOrFail();
        $product = Product::query()->whereKey($sku->product_id)->firstOrFail();
        if (! ShopProductStatus::isOnSale((int) $product->status) || (int) $sku->status !== 1) {
            throw new RuntimeException(__('shop::messages.product_not_on_sale'));
        }

        $cart = $this->cartForMember((int) $data['member_id']);
        $qty = (int) ($data['quantity'] ?? 1);

        $item = CartItem::query()->firstOrNew([
            'cart_id' => $cart->id,
            'sku_id' => $sku->id,
        ]);
        $item->product_id = $product->id;
        $item->quantity = ($item->exists ? (int) $item->quantity : 0) + $qty;
        $item->save();

        return ApiResponse::success($cart->fresh(['items.product', 'items.sku']), __('shop::messages.cart_updated'));
    }

    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->cartForMember((int) $data['member_id']);
        $item = CartItem::query()->where('cart_id', $cart->id)->whereKey($itemId)->firstOrFail();
        $item->update(['quantity' => (int) $data['quantity']]);

        return ApiResponse::success($cart->fresh(['items.product', 'items.sku']), __('shop::messages.cart_updated'));
    }

    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $memberId = (int) $request->validate(['member_id' => ['required', 'integer', 'min:1']])['member_id'];
        $cart = $this->cartForMember($memberId);
        CartItem::query()->where('cart_id', $cart->id)->whereKey($itemId)->delete();

        return ApiResponse::success($cart->fresh(['items.product', 'items.sku']), __('shop::messages.cart_updated'));
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer', 'min:1'],
            'item_ids' => ['nullable', 'array'],
            'item_ids.*' => ['integer'],
            'fulfillment_payload' => ['nullable', 'array'],
        ]);

        $cart = $this->cartForMember((int) $data['member_id']);
        $query = CartItem::query()->where('cart_id', $cart->id);
        if (! empty($data['item_ids'])) {
            $query->whereIn('id', $data['item_ids']);
        }
        $items = $query->get();
        if ($items->isEmpty()) {
            throw new RuntimeException(__('shop::messages.cart_empty'));
        }

        // 最小切片：取第一行创建订单（多商品合并下单留待后续）
        $first = $items->first();
        $order = $this->orders->createFromProduct(
            (int) $first->product_id,
            (int) $first->quantity,
            (int) $data['member_id'],
            null,
            (int) $first->sku_id,
            $data['fulfillment_payload'] ?? [],
        );

        CartItem::query()->whereKey($first->id)->delete();

        return ApiResponse::success($order, __('shop::messages.order_created'));
    }

    protected function cartForMember(int $memberId): Cart
    {
        return Cart::query()->firstOrCreate(['member_id' => $memberId]);
    }
}
