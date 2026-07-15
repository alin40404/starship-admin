<?php

namespace Starship\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Support\Hook\Hook;
use Starship\Core\Tests\TestCase;

class HookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Hook::reset();
    }

    protected function tearDown(): void
    {
        Hook::reset();
        parent::tearDown();
    }

    #[Test]
    public function actions_run_in_priority_order(): void
    {
        $order = [];

        add_action('demo.action', static function () use (&$order): void {
            $order[] = 'b';
        }, 20);
        add_action('demo.action', static function () use (&$order): void {
            $order[] = 'a';
        }, 5);

        do_action('demo.action');

        $this->assertSame(['a', 'b'], $order);
    }

    #[Test]
    public function filters_transform_value(): void
    {
        add_filter('demo.filter', static fn (string $value): string => $value.'-1');
        add_filter('demo.filter', static fn (string $value): string => $value.'-2', 20);

        $this->assertSame('base-1-2', apply_filters('demo.filter', 'base'));
    }
}
