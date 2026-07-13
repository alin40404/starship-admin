<?php

namespace Modules\Generator\Tests\Unit;

use Modules\Generator\App\Support\StatusFieldResolver;
use PHPUnit\Framework\TestCase;

final class StatusFieldResolverTest extends TestCase
{
    public function test_string_status_from_comment(): void
    {
        $profile = StatusFieldResolver::fromColumnTypeAndComment(
            'varchar(30)',
            '状态: normal,hidden',
        );

        $this->assertFalse($profile->isInteger());
        $this->assertSame('normal', $profile->enabledValue);
        $this->assertSame('hidden', $profile->disabledValue);
        $this->assertSame('in:normal,hidden', $profile->laravelInRule());
    }

    public function test_integer_status_from_column_type(): void
    {
        $profile = StatusFieldResolver::fromColumnTypeAndComment(
            'tinyint',
            '状态:1启用,0禁用',
        );

        $this->assertTrue($profile->isInteger());
        $this->assertSame(1, $profile->enabledValue);
        $this->assertSame(0, $profile->disabledValue);
    }

    public function test_string_column_ignores_legacy_numeric_in_rule(): void
    {
        $profile = StatusFieldResolver::fromColumnTypeAndComment(
            'varchar(30)',
            '状态: normal,hidden',
            ['required', 'in:0,1'],
        );

        $this->assertFalse($profile->isInteger());
        $this->assertSame('in:normal,hidden', $profile->laravelInRule());
    }
}
