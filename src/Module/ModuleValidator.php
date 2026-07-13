<?php

namespace Starship\Core\Module;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class ModuleValidator
{
    /**
     * @param  Collection<int, Module>  $enabledModules
     */
    public function assertEnabledDependencies(Module $module, Collection $enabledModules): void
    {
        if ($module->requireModules() === []) {
            return;
        }

        $available = $enabledModules
            ->map(static fn (Module $item) => strtolower($item->name()))
            ->all();

        foreach ($module->requireModules() as $dependency) {
            if (! in_array(strtolower($dependency), $available, true)) {
                throw new InvalidArgumentException(
                    "模块 {$module->name()} 依赖未启用的模块：{$dependency}"
                );
            }
        }
    }

    /**
     * @param  Collection<int, Module>  $enabledModules
     */
    public function assertAll(Collection $enabledModules): void
    {
        foreach ($enabledModules as $module) {
            $this->assertEnabledDependencies($module, $enabledModules);
        }
    }
}
