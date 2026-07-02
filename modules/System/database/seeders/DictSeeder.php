<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\DictItem;
use Modules\System\App\Models\DictType;

class DictSeeder extends Seeder
{
    public function run(): void
    {

        $type = DictType::query()->updateOrCreate(
            ['code' => 'sys_status'],
            [
                'name' => '通用状态',
                'status' => 1,
                'remark' => '启用 / 禁用',
            ]
        );

        DictItem::query()->updateOrCreate(
            ['dict_type_id' => $type->id, 'value' => '1'],
            ['label' => '启用', 'sort' => 1, 'status' => 1]
        );

        DictItem::query()->updateOrCreate(
            ['dict_type_id' => $type->id, 'value' => '0'],
            ['label' => '禁用', 'sort' => 2, 'status' => 1]
        );

        $attachmentCategory = DictType::query()->updateOrCreate(
            ['code' => 'attachment_category'],
            [
                'name' => '附件分类',
                'status' => 1,
                'remark' => '附件管理分类',
            ]
        );

        $categories = [
            ['value' => 'default', 'label' => '默认', 'sort' => 0],
            ['value' => 'image', 'label' => '图片', 'sort' => 10],
            ['value' => 'video', 'label' => '视频', 'sort' => 20],
            ['value' => 'document', 'label' => '文档', 'sort' => 30],
            ['value' => 'avatar', 'label' => '头像', 'sort' => 40],
            ['value' => 'resources', 'label' => '资源', 'sort' => 50],
        ];

        foreach ($categories as $item) {
            DictItem::query()->updateOrCreate(
                [
                    'dict_type_id' => $attachmentCategory->id,
                    'value' => $item['value'],
                ],
                [
                    'label' => $item['label'],
                    'sort' => $item['sort'],
                    'status' => 1,
                ],
            );
        }

        $categoryType = DictType::query()->updateOrCreate(
            ['code' => 'category_type'],
            [
                'name' => '栏目类型',
                'status' => 1,
                'remark' => '分类管理栏目类型',
            ]
        );

        $categoryTypes = [
            ['value' => 'default', 'label' => '默认', 'sort' => 0],
            ['value' => 'page', 'label' => '单页', 'sort' => 10],
            ['value' => 'article', 'label' => '文章', 'sort' => 20],
            ['value' => 'test', 'label' => '测试', 'sort' => 30],
        ];

        foreach ($categoryTypes as $item) {
            DictItem::query()->updateOrCreate(
                [
                    'dict_type_id' => $categoryType->id,
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
