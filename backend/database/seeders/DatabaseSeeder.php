<?php

namespace Database\Seeders;

use Starship\Core\Module\ModuleManager;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * 按各模块 module.json 中的 seeders 声明，依次执行启用模块的种子。
     * 模块顺序遵循 require.modules（如 system 先于 member）。
     */
    public function run(): void
    {
        $manager = app(ModuleManager::class);

        foreach ($manager->enabled() as $module) {
            foreach ($module->seeders() as $seeder) {
                if (! class_exists($seeder)) {
                    throw new \RuntimeException(
                        "模块 [{$module->name()}] 的 Seeder 类不存在：{$seeder}。请在 backend 目录执行 composer update 安装对应模块包。"
                    );
                }
                $this->call($seeder);
            }
        }
    }
}
