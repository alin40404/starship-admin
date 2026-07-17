<?php

namespace Modules\Shop\App\Support;

/**
 * 普通商城商品类型（字典 shop_product_type）。
 * 游戏/课程/预约等业态由独立 Module 承接，不进入本枚举。
 */
final class ShopProductType
{
    public const DICT_TYPE_CODE = 'shop_product_type';

    public const PHYSICAL = 'physical';

    public const VIRTUAL = 'virtual';

    public const DIGITAL = 'digital';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::PHYSICAL,
            self::VIRTUAL,
            self::DIGITAL,
        ];
    }

    public static function isValid(?string $type): bool
    {
        return $type !== null && $type !== '' && in_array($type, self::values(), true);
    }
}
