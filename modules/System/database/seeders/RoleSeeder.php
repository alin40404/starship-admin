<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::query()->updateOrCreate(
            ['code' => 'super_admin'],
            [
                'name' => '超级管理员',
                'data_scope' => 1,
                'status' => 1,
                'remark' => '系统内置，绑定全部菜单',
            ]
        );

        Role::query()->updateOrCreate(
            ['code' => 'normal_admin'],
            [
                'name' => '普通管理员',
                'data_scope' => 3,
                'status' => 1,
                'remark' => '日常业务管理，不含权限中心与开发工具',
            ]
        );
    }
}
