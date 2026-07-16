<?php

namespace Modules\Shop\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Module\ModuleManager;
use Tests\TestCase;

class ShopModuleSmokeTest extends TestCase
{
    #[Test]
    public function shop_module_is_enabled(): void
    {
        $names = app(ModuleManager::class)
            ->enabled()
            ->map(static fn ($module) => strtolower($module->name()))
            ->values()
            ->all();

        $this->assertContains('shop', $names);
    }
}
