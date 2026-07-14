<?php

namespace Starship\Core\Tests\Unit;

use InvalidArgumentException;
use Starship\Core\Module\Module;
use Starship\Core\Module\ModuleManager;
use Starship\Core\Tests\TestCase;

class ModuleDependencyOrderTest extends TestCase
{
    public function test_sort_by_dependencies_puts_required_modules_first(): void
    {
        $manager = $this->app->make(ModuleManager::class);

        $sorted = $manager->sortByDependencies(collect([
            new Module([
                'name' => 'member',
                'enabled' => true,
                'require' => ['modules' => ['system']],
            ]),
            new Module([
                'name' => 'system',
                'enabled' => true,
            ]),
            new Module([
                'name' => 'generator',
                'enabled' => true,
                'require' => ['modules' => ['system']],
            ]),
        ]));

        $names = $sorted->map(static fn (Module $module) => $module->name())->all();

        $this->assertSame('system', $names[0]);
        $this->assertContains('member', $names);
        $this->assertContains('generator', $names);
        $this->assertSame(3, count($names));
    }

    public function test_sort_supports_nested_dependency_chain(): void
    {
        $manager = $this->app->make(ModuleManager::class);

        $sorted = $manager->sortByDependencies(collect([
            new Module([
                'name' => 'shop',
                'enabled' => true,
                'require' => ['modules' => ['member']],
            ]),
            new Module([
                'name' => 'member',
                'enabled' => true,
                'require' => ['modules' => ['system']],
            ]),
            new Module([
                'name' => 'system',
                'enabled' => true,
            ]),
        ]));

        $this->assertSame(
            ['system', 'member', 'shop'],
            $sorted->map(static fn (Module $module) => $module->name())->all(),
        );
    }

    public function test_sort_throws_on_dependency_cycle(): void
    {
        $manager = $this->app->make(ModuleManager::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('模块依赖存在循环');

        $manager->sortByDependencies(collect([
            new Module([
                'name' => 'alpha',
                'enabled' => true,
                'require' => ['modules' => ['beta']],
            ]),
            new Module([
                'name' => 'beta',
                'enabled' => true,
                'require' => ['modules' => ['alpha']],
            ]),
        ]));
    }
}
