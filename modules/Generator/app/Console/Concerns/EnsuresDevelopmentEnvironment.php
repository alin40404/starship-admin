<?php

namespace Modules\Generator\App\Console\Concerns;

use Starship\Core\Support\DevEnvironment;

trait EnsuresDevelopmentEnvironment
{
    protected function abortUnlessDevelopmentEnvironment(): ?int
    {
        if (DevEnvironment::isDevelopment()) {
            return null;
        }

        $this->error(__('generator::messages.dev_only'));

        return self::FAILURE;
    }
}
