<?php

namespace Starship\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Module\Module;
use Starship\Core\Tests\TestCase;

class ModulePermissionsTest extends TestCase
{
    #[Test]
    public function prefixes_without_dot_are_normalized_with_trailing_dot(): void
    {
        $module = new Module([
            'name' => 'member',
            'permissions' => [
                'prefixes' => ['member', 'dashboard'],
                'exact' => [],
            ],
        ]);

        $this->assertSame(['member.', 'dashboard.'], $module->permissions()['prefixes']);
    }

    #[Test]
    public function prefixes_with_trailing_dot_are_not_doubled(): void
    {
        $module = new Module([
            'name' => 'system',
            'permissions' => [
                'prefixes' => ['system.', 'monitor.'],
                'exact' => [],
            ],
        ]);

        $this->assertSame(['system.', 'monitor.'], $module->permissions()['prefixes']);
    }

    #[Test]
    public function empty_and_non_string_prefixes_are_filtered(): void
    {
        $module = new Module([
            'name' => 'demo',
            'permissions' => [
                'prefixes' => ['demo', '', null, 1, '.'],
                'exact' => ['demo.special'],
            ],
        ]);

        $this->assertSame(['demo.'], $module->permissions()['prefixes']);
        $this->assertSame(['demo.special'], $module->permissions()['exact']);
    }
}
