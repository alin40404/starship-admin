<?php

namespace Modules\Generator\Tests\Unit;

use Modules\Generator\App\Support\ModulePathResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModulePathResolverTest extends TestCase
{
    #[Test]
    public function canonical_module_slug_is_case_insensitive(): void
    {
        $resolver = ModulePathResolver::make();

        $this->assertSame('system', $resolver->canonicalModuleSlug('System'));
        $this->assertSame('system', $resolver->canonicalModuleSlug('system'));
        $this->assertSame('generator', $resolver->canonicalModuleSlug('Generator'));
    }

    #[Test]
    public function module_exists_is_case_insensitive(): void
    {
        $resolver = ModulePathResolver::make();

        $this->assertTrue($resolver->moduleExists('system'));
        $this->assertTrue($resolver->moduleExists('System'));
        $this->assertFalse($resolver->moduleExists('not-a-module'));
    }
}
