<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Admin;

class SystemAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) env('SYSTEM_ADMIN_PASSWORD', 'admin123');

        Admin::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'nickname' => '超级管理员',
                'password' => $password,
                'status' => 1,
                'is_super' => 1,
            ]
        );
    }
}
