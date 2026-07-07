<?php

namespace Modules\Generator\App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Generator\App\Models\GeneratorRecord;
use Modules\Generator\App\Models\GeneratorTable;

class GeneratorCodeGenerated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $result
     */
    public function __construct(
        public GeneratorTable $tableConfig,
        public GeneratorRecord $record,
        public array $result,
    ) {}
}
