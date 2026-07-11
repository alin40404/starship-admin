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
     */
    public function run(): void
    {
        $manager = app(ModuleManager::class);

        foreach ($manager->enabled() as $module) {
            foreach ($module->seeders() as $seeder) {
                $this->call($seeder);
            }
        }
    }
}
