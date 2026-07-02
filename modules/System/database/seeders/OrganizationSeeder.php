<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Department;
use Modules\System\App\Models\Post;
use Modules\System\Database\Seeders\Concerns\SeedsOrganization;

class OrganizationSeeder extends Seeder
{
    use SeedsOrganization;

    public function run(): void
    {
        $this->seedDepartments();
        $this->seedPosts();
    }

    private function seedDepartments(): Department
    {

        $root = Department::query()->updateOrCreate(
            ['parent_id' => 0, 'name' => '总公司'],
            [
                'phone' => '010-88880000',
                'email' => 'ceo@starship.org',
                'sort' => 0,
                'status' => 1,
                'remark' => '集团总部',
            ],
        );

        $this->createDepartmentTree([
            [
                'name' => '研发中心',
                'phone' => '010-88880001',
                'sort' => 10,
                'children' => [
                    ['name' => '前端组', 'sort' => 1],
                    ['name' => '后端组', 'sort' => 2],
                    ['name' => '测试组', 'sort' => 3],
                ],
            ],
            [
                'name' => '市场营销部',
                'phone' => '010-88880002',
                'sort' => 20,
            ],
            [
                'name' => '人力资源部',
                'phone' => '010-88880003',
                'sort' => 30,
            ],
            [
                'name' => '财务部',
                'phone' => '010-88880004',
                'sort' => 40,
                'status' => 0,
                'remark' => '示例：禁用状态',
            ],
        ], (int) $root->id);

        return $root;
    
    }

    private function seedPosts(): void
    {

        $posts = [
            ['name' => '总经理', 'code' => 'ceo', 'sort' => 0, 'status' => 1, 'remark' => '公司最高管理者'],
            ['name' => '研发总监', 'code' => 'rd_director', 'sort' => 10, 'status' => 1, 'remark' => '研发中心负责人'],
            ['name' => '前端工程师', 'code' => 'frontend_engineer', 'sort' => 11, 'status' => 1, 'remark' => '对应前端组'],
            ['name' => '后端工程师', 'code' => 'backend_engineer', 'sort' => 12, 'status' => 1, 'remark' => '对应后端组'],
            ['name' => '测试工程师', 'code' => 'qa_engineer', 'sort' => 13, 'status' => 1, 'remark' => '对应测试组'],
            ['name' => '市场经理', 'code' => 'marketing_manager', 'sort' => 20, 'status' => 1, 'remark' => '市场营销部'],
            ['name' => '销售专员', 'code' => 'sales_specialist', 'sort' => 21, 'status' => 1, 'remark' => null],
            ['name' => '人事专员', 'code' => 'hr_specialist', 'sort' => 30, 'status' => 1, 'remark' => '人力资源部'],
            ['name' => '财务专员', 'code' => 'finance_specialist', 'sort' => 40, 'status' => 0, 'remark' => '示例：禁用状态'],
        ];

        foreach ($posts as $post) {
            Post::query()->updateOrCreate(
                ['code' => $post['code']],
                [
                    'name' => $post['name'],
                    'sort' => $post['sort'],
                    'status' => $post['status'],
                    'remark' => $post['remark'],
                ],
            );
        }
    
    }
}
