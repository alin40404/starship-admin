<?php

namespace Modules\Cms\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Module\ModuleManager;
use Tests\TestCase;

class CmsModuleSmokeTest extends TestCase
{
    #[Test]
    public function cms_module_is_enabled(): void
    {
        $names = app(ModuleManager::class)
            ->enabled()
            ->map(static fn ($module) => strtolower($module->name()))
            ->values()
            ->all();

        $this->assertContains('cms', $names);
    }
}
