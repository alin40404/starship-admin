<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {

        if (Category::query()->exists()) {
            return;
        }

        $rows = [
            ['id' => 1, 'parent_id' => 0, 'type' => 'page', 'name' => '官方新闻', 'nickname' => 'news', 'flag' => 'recommend', 'diyname' => 'news', 'sort' => 1],
            ['id' => 2, 'parent_id' => 0, 'type' => 'page', 'name' => '移动应用', 'nickname' => 'mobileapp', 'flag' => 'hot', 'diyname' => 'mobileapp', 'sort' => 2],
            ['id' => 3, 'parent_id' => 2, 'type' => 'page', 'name' => '微信公众号', 'nickname' => 'wechatpublic', 'flag' => 'index', 'diyname' => 'wechatpublic', 'sort' => 3],
            ['id' => 4, 'parent_id' => 2, 'type' => 'page', 'name' => 'Android开发', 'nickname' => 'android', 'flag' => 'recommend', 'diyname' => 'android', 'sort' => 4],
            ['id' => 5, 'parent_id' => 0, 'type' => 'page', 'name' => '软件产品', 'nickname' => 'software', 'flag' => 'recommend', 'diyname' => 'software', 'sort' => 5],
            ['id' => 6, 'parent_id' => 5, 'type' => 'page', 'name' => '网站建站', 'nickname' => 'website', 'flag' => 'recommend', 'diyname' => 'website', 'sort' => 6],
            ['id' => 7, 'parent_id' => 5, 'type' => 'page', 'name' => '企业管理软件', 'nickname' => 'company', 'flag' => 'index', 'diyname' => 'company', 'sort' => 7],
            ['id' => 8, 'parent_id' => 6, 'type' => 'page', 'name' => 'PC端', 'nickname' => 'website-pc', 'flag' => 'recommend', 'diyname' => 'website-pc', 'sort' => 8],
            ['id' => 9, 'parent_id' => 6, 'type' => 'page', 'name' => '移动端', 'nickname' => 'website-mobile', 'flag' => 'recommend', 'diyname' => 'website-mobile', 'sort' => 9],
            ['id' => 10, 'parent_id' => 7, 'type' => 'page', 'name' => 'CRM系统', 'nickname' => 'company-crm', 'flag' => 'recommend', 'diyname' => 'company-crm', 'sort' => 10],
            ['id' => 11, 'parent_id' => 7, 'type' => 'page', 'name' => 'SASS平台软件', 'nickname' => 'company-sass', 'flag' => 'recommend', 'diyname' => 'company-sass', 'sort' => 11],
            ['id' => 12, 'parent_id' => 0, 'type' => 'test', 'name' => '测试1', 'nickname' => 'test1', 'flag' => 'recommend', 'diyname' => 'test1', 'sort' => 12],
            ['id' => 13, 'parent_id' => 0, 'type' => 'test', 'name' => '测试2', 'nickname' => 'test2', 'flag' => 'recommend', 'diyname' => 'test2', 'sort' => 13],
        ];

        foreach ($rows as $row) {
            Category::query()->updateOrCreate(
                ['id' => $row['id']],
                [
                    'parent_id' => $row['parent_id'],
                    'type' => $row['type'],
                    'name' => $row['name'],
                    'nickname' => $row['nickname'],
                    'flag' => $row['flag'],
                    'image' => '/assets/img/qrcode.png',
                    'keywords' => '',
                    'description' => '',
                    'diyname' => $row['diyname'],
                    'sort' => $row['sort'],
                    'status' => 'normal',
                ],
            );
        }
        }
}
