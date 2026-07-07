<?php

namespace Modules\Generator\App\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Generator\App\Events\GeneratorCodeGenerated;

class LogGeneratorCodeGenerated
{
    public function handle(GeneratorCodeGenerated $event): void
    {
        $payload = [
            'table' => $event->tableConfig->table_name,
            'source' => $event->record->source,
            'operator' => $event->record->operator_name,
            'files' => collect($event->record->files)->pluck('path')->all(),
        ];

        Log::info('generator.code.generated', $payload);

        if (! DB::getSchemaBuilder()->hasTable('sa_monitor_operation_logs')) {
            return;
        }

        DB::table('sa_monitor_operation_logs')->insert([
            'admin_id' => $event->record->operator_id,
            'module' => 'generator',
            'action' => 'code.generate',
            'title' => '代码生成：'.$event->tableConfig->business_name,
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ip' => '127.0.0.1',
            'user_agent' => 'generator-cli',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
