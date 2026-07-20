<?php

namespace Modules\Shop\App\Support;

/**
 * 普通商城商品状态。
 * 0=下架, 1=上架, 2=草稿
 */
final class ShopProductStatus
{
    public const OFF = 0;

    public const ON = 1;

    public const DRAFT = 2;

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return [
            self::OFF,
            self::ON,
            self::DRAFT,
        ];
    }

    public static function isOnSale(int $status): bool
    {
        return $status === self::ON;
    }
}
