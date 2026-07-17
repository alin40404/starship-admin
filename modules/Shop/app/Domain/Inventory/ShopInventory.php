<?php

namespace Modules\Shop\App\Domain\Inventory;

use Modules\Shop\App\Contracts\InventoryInterface;
use Modules\Shop\App\Models\Product;
use Modules\Shop\App\Models\ProductSku;
use RuntimeException;

class ShopInventory implements InventoryInterface
{
    public function decreaseSkuStock(int $skuId, int $quantity): void
    {
        $sku = ProductSku::query()->whereKey($skuId)->lockForUpdate()->firstOrFail();
        if ((int) $sku->stock < $quantity) {
            throw new RuntimeException(__('shop::messages.product_out_of_stock'));
        }
        $sku->decrement('stock', $quantity);
        $this->syncProductStock((int) $sku->product_id);
    }

    public function increaseSkuStock(int $skuId, int $quantity): void
    {
        $sku = ProductSku::query()->whereKey($skuId)->lockForUpdate()->firstOrFail();
        $sku->increment('stock', $quantity);
        $this->syncProductStock((int) $sku->product_id);
    }

    public function availableSkuStock(int $skuId): int
    {
        return (int) ProductSku::query()->whereKey($skuId)->value('stock');
    }

    protected function syncProductStock(int $productId): void
    {
        $total = (int) ProductSku::query()
            ->where('product_id', $productId)
            ->sum('stock');

        Product::query()->whereKey($productId)->update(['stock' => $total]);
    }
}
