<?php

namespace Modules\Generator\App\Services;

use Illuminate\Support\Facades\Event;
use Modules\Generator\App\Events\GeneratorCodeGenerated;
use Modules\Generator\App\Models\GeneratorRecord;
use Modules\Generator\App\Models\GeneratorTable;

class GeneratorAuditLogger
{
    /**
     * @param  array<string, mixed>  $result
     */
    public function log(
        GeneratorTable $config,
        array $result,
        string $source = 'cli',
        ?int $operatorId = null,
        ?string $operatorName = null,
        ?array $options = null,
    ): GeneratorRecord {
        $files = collect($result['written'] ?? [])
            ->map(fn (array $item): array => [
                'path' => $item['path'],
                'action' => $item['action'],
            ])
            ->values()
            ->all();

        $record = GeneratorRecord::query()->create([
            'generator_table_id' => $config->id,
            'files' => $files,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName ?? ($source === 'cli' ? 'cli' : 'web'),
            'source' => $source,
            'options' => $options,
        ]);

        Event::dispatch(new GeneratorCodeGenerated($config, $record, $result));

        return $record;
    }
}
