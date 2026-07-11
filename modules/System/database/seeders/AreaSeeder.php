<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\System\App\Models\Area;
use RuntimeException;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        if (Area::query()->exists()) {
            return;
        }

        $path = dirname(__DIR__).'/sql/seed_areas.sql';
        if (! is_readable($path)) {
            throw new RuntimeException("Area seed SQL not found: {$path}");
        }

        $area = new Area;
        $table = $area->getConnection()->getTablePrefix().$area->getTable();

        $sql = file_get_contents($path);
        $sql = str_replace('`sa_areas`', '`'.$table.'`', $sql);
        $sql = preg_replace('/^--.*$/m', '', $sql);

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            static fn (string $statement): bool => $statement !== '',
        );

        foreach ($statements as $statement) {
            DB::connection($area->getConnectionName())->unprepared($statement);
        }
    }
}
