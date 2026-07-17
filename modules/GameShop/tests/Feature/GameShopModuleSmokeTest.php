<?php

namespace Modules\GameShop\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Module\ModuleManager;
use Tests\TestCase;

class GameShopModuleSmokeTest extends TestCase
{
    #[Test]
    public function game_shop_module_is_enabled(): void
    {
        $names = app(ModuleManager::class)
            ->enabled()
            ->map(static fn ($module) => strtolower($module->name()))
            ->values()
            ->all();

        $this->assertContains('game_shop', $names);
    }
}
