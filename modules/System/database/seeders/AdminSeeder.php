<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Admin;
use Modules\System\App\Models\Department;
use Modules\System\App\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $dept = Department::query()
            ->where('parent_id', 0)
            ->where('name', '总公司')
            ->first();

        if (! $dept) {
            $this->command?->warn('AdminSeeder: 未找到根部门，跳过默认管理员初始化');

            return;
        }

        $role = Role::query()->where('code', 'super_admin')->first();
        $password = (string) env('SYSTEM_ADMIN_PASSWORD', 'admin123');

        $admin = Admin::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'nickname' => '超级管理员',
                'password' => $password,
                'email' => 'admin@starship.org',
                'mobile' => '13800000000',
                'dept_id' => $dept->id,
                'status' => 1,
                'is_super' => 1,
            ]
        );

        if ($role) {
            $admin->roles()->sync([$role->id]);
        }

        Department::query()->whereKey($dept->id)->update(['leader_id' => $admin->id]);

        $normalRole = Role::query()->where('code', 'normal_admin')->first();
        $rdDept = Department::query()
            ->where('parent_id', $dept->id)
            ->where('name', '研发中心')
            ->first();
        $normalPassword = (string) env(
            'SYSTEM_NORMAL_ADMIN_PASSWORD',
            env('SYSTEM_ADMIN_PASSWORD', 'admin123'),
        );

        $normalAdmin = Admin::query()->updateOrCreate(
            ['username' => 'manager'],
            [
                'nickname' => '普通管理员',
                'password' => $normalPassword,
                'email' => 'manager@starship.org',
                'mobile' => '13800000001',
                'dept_id' => $rdDept?->id ?? $dept->id,
                'status' => 1,
                'is_super' => 0,
                'remark' => '默认普通管理员账号',
            ]
        );

        if ($normalRole) {
            $normalAdmin->roles()->sync([$normalRole->id]);
        }
    }
}
