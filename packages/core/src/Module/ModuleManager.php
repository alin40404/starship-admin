<?php

namespace Starship\Core\Module;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ModuleManager
{
    public function __construct(
        protected string $modulePath
    ) {}

    public function all(): Collection
    {
        if (! File::isDirectory($this->modulePath)) {
            return collect();
        }

        $modules = [];

        foreach (File::directories($this->modulePath) as $directory) {
            $manifestPath = $directory.DIRECTORY_SEPARATOR.'module.json';
            $data = ModuleManifest::load($manifestPath);

            if (! $data) {
                continue;
            }

            $modules[] = new Module($data);
        }

        return collect($modules);
    }

    public function enabled(): Collection
    {
        return $this->all()->filter(fn (Module $module) => $module->enabled())->values();
    }
}
