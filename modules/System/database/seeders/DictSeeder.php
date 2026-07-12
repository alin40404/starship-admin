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
            ['value' => 'image', 'label' => '图片', 'sort' => 10],
            ['value' => 'video', 'label' => '视频', 'sort' => 20],
            ['value' => 'audio', 'label' => '音频', 'sort' => 25],
            ['value' => 'document', 'label' => '文档', 'sort' => 30],
            ['value' => 'resources', 'label' => '资源', 'sort' => 40],
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

        DictItem::query()
            ->where('dict_type_id', $attachmentCategory->id)
            ->whereIn('value', ['default', 'avatar'])
            ->update(['status' => 0]);

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

        $siteTimezone = DictType::query()->updateOrCreate(
            ['code' => 'site_timezone'],
            [
                'name' => '站点时区',
                'status' => 1,
                'remark' => '平台配置-站点默认展示时区',
            ]
        );

        $timezones = [
            ['value' => 'UTC', 'label' => 'UTC (GMT+0)', 'sort' => 10],
            ['value' => 'Asia/Shanghai', 'label' => 'Asia/Shanghai (GMT+8)', 'sort' => 20],
            ['value' => 'Asia/Hong_Kong', 'label' => 'Asia/Hong_Kong (GMT+8)', 'sort' => 30],
            ['value' => 'Asia/Taipei', 'label' => 'Asia/Taipei (GMT+8)', 'sort' => 40],
            ['value' => 'Asia/Tokyo', 'label' => 'Asia/Tokyo (GMT+9)', 'sort' => 50],
            ['value' => 'Asia/Seoul', 'label' => 'Asia/Seoul (GMT+9)', 'sort' => 60],
            ['value' => 'Asia/Singapore', 'label' => 'Asia/Singapore (GMT+8)', 'sort' => 70],
            ['value' => 'Europe/London', 'label' => 'Europe/London (GMT+0)', 'sort' => 80],
            ['value' => 'Europe/Paris', 'label' => 'Europe/Paris (GMT+1)', 'sort' => 90],
            ['value' => 'America/New_York', 'label' => 'America/New_York (GMT-5)', 'sort' => 100],
            ['value' => 'America/Los_Angeles', 'label' => 'America/Los_Angeles (GMT-8)', 'sort' => 110],
        ];

        foreach ($timezones as $item) {
            DictItem::query()->updateOrCreate(
                [
                    'dict_type_id' => $siteTimezone->id,
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
