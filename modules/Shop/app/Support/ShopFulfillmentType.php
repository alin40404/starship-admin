<?php

namespace Modules\Shop\App\Support;

/**
 * 普通商城交付方式（字典 shop_fulfillment_type）。
 * 字段名 fulfillment_type；后台 UI 展示为「交付方式」。
 */
final class ShopFulfillmentType
{
    public const DICT_TYPE_CODE = 'shop_fulfillment_type';

    public const SHIPPING = 'shipping';

    public const DIGITAL = 'digital';

    public const CARD = 'card';

    public const CODE = 'code';

    public const MANUAL = 'manual';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::SHIPPING,
            self::DIGITAL,
            self::CARD,
            self::CODE,
            self::MANUAL,
        ];
    }

    public static function isValid(?string $type): bool
    {
        return $type !== null && $type !== '' && in_array($type, self::values(), true);
    }

    public static function usesCardPool(?string $type): bool
    {
        return in_array($type, [self::CARD, self::CODE], true);
    }

    /**
     * 商品类型允许的交付方式；首个为默认值。
     *
     * @return list<string>
     */
    public static function allowedForProductType(?string $productType): array
    {
        return match ($productType) {
            ShopProductType::VIRTUAL => [self::DIGITAL, self::CARD, self::CODE, self::MANUAL],
            ShopProductType::DIGITAL => [self::DIGITAL, self::CODE, self::MANUAL],
            default => [self::SHIPPING, self::MANUAL],
        };
    }

    public static function isAllowedForProductType(?string $type, ?string $productType): bool
    {
        return in_array($type, self::allowedForProductType($productType), true);
    }

    public static function defaultForProductType(?string $productType): string
    {
        return self::allowedForProductType($productType)[0];
    }
}
