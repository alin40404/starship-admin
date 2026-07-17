<?php

namespace Modules\Shop\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\DictItem;
use Modules\System\App\Models\DictType;

/**
 * Shop 依赖 System，字典项由本模块维护。
 */
class ShopDictSeeder extends Seeder
{
    public function run(): void
    {
        $categoryType = DictType::query()->updateOrCreate(
            ['code' => 'category_type'],
            [
                'name' => '栏目类型',
                'status' => 1,
                'remark' => '分类管理栏目类型',
            ]
        );

        DictItem::query()->updateOrCreate(
            [
                'dict_type_id' => $categoryType->id,
                'value' => 'product',
            ],
            [
                'label' => '商品',
                'sort' => 25,
                'status' => 1,
            ],
        );

        $shopProductType = DictType::query()->updateOrCreate(
            ['code' => 'shop_product_type'],
            [
                'name' => '商城商品类型',
                'status' => 1,
                'remark' => '商城商品类型',
            ]
        );

        $shopProductTypes = [
            ['value' => 'physical', 'label' => '实物商品', 'sort' => 10],
            ['value' => 'virtual', 'label' => '虚拟商品', 'sort' => 20],
            ['value' => 'digital', 'label' => '数字资源', 'sort' => 30],
        ];

        foreach ($shopProductTypes as $item) {
            DictItem::query()->updateOrCreate(
                [
                    'dict_type_id' => $shopProductType->id,
                    'value' => $item['value'],
                ],
                [
                    'label' => $item['label'],
                    'sort' => $item['sort'],
                    'status' => 1,
                ],
            );
        }

        $fulfillmentType = DictType::query()->updateOrCreate(
            ['code' => 'shop_fulfillment_type'],
            [
                'name' => '商城交付方式',
                'status' => 1,
                'remark' => '商城交付方式',
            ]
        );

        $fulfillmentTypes = [
            ['value' => 'shipping', 'label' => '物流配送', 'sort' => 10],
            ['value' => 'digital', 'label' => '自动发放', 'sort' => 20],
            ['value' => 'card', 'label' => '卡券发放', 'sort' => 30],
            ['value' => 'code', 'label' => '兑换码', 'sort' => 40],
            ['value' => 'manual', 'label' => '人工处理', 'sort' => 50],
        ];

        foreach ($fulfillmentTypes as $item) {
            DictItem::query()->updateOrCreate(
                [
                    'dict_type_id' => $fulfillmentType->id,
                    'value' => $item['value'],
                ],
                [
                    'label' => $item['label'],
                    'sort' => $item['sort'],
                    'status' => 1,
                ],
            );
        }
    }
}
