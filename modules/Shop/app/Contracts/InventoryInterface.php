<?php

namespace Modules\Shop\App\Contracts;

/**
 * 商城库存契约：默认本模块实现，可替换为其它库存实现。
 * 属于 Shop 业务能力，不进入 Core。
 */
interface InventoryInterface
{
    public function decreaseSkuStock(int $skuId, int $quantity): void;

    public function increaseSkuStock(int $skuId, int $quantity): void;

    public function availableSkuStock(int $skuId): int;
}
