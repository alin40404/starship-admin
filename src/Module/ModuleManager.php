<?php

namespace Starship\Core\Module;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

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
        return $this->sortByDependencies(
            $this->all()->filter(fn (Module $module) => $module->enabled())->values()
        );
    }

    /**
     * 按 require.modules 做 DFS 拓扑排序（支持任意深度嵌套）。
     *
     * 例：shop → member → system 会排成 system, member, shop。
     * 环依赖会抛 InvalidArgumentException（不应静默跳过）。
     * 依赖未出现在本集合中时忽略（启用校验仍由 ModuleValidator 负责）。
     *
     * @param  Collection<int, Module>  $modules
     * @return Collection<int, Module>
     */
    public function sortByDependencies(Collection $modules): Collection
    {
        /** @var array<string, Module> $byName */
        $byName = [];
        foreach ($modules as $module) {
            $byName[strtolower($module->name())] = $module;
        }

        $sorted = [];
        $visiting = [];
        $visited = [];

        $visit = function (string $name, array $stack = []) use (&$visit, &$byName, &$sorted, &$visiting, &$visited): void {
            $key = strtolower($name);
            if (isset($visited[$key]) || ! isset($byName[$key])) {
                return;
            }
            if (isset($visiting[$key])) {
                $cycle = implode(' → ', [...$stack, $name]);
                throw new InvalidArgumentException("模块依赖存在循环：{$cycle}");
            }

            $visiting[$key] = true;
            $module = $byName[$key];
            $nextStack = [...$stack, $module->name()];
            foreach ($module->requireModules() as $dependency) {
                $visit($dependency, $nextStack);
            }
            unset($visiting[$key]);
            $visited[$key] = true;
            $sorted[] = $module;
        };

        // 入口按名字排序，保证同层无依赖时顺序稳定（与目录扫描顺序解耦）
        foreach (collect(array_keys($byName))->sort()->all() as $name) {
            $visit($name);
        }

        return collect($sorted)->values();
    }
}