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
    }
}
