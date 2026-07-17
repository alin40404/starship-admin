<?php

namespace Modules\Shop\App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shop\App\Domain\Fulfillment\FulfillmentDispatcher;
use Modules\Shop\App\Domain\Inventory\ShopInventory;
use Modules\Shop\App\Contracts\FulfillmentInterface;
use Modules\Shop\App\Contracts\InventoryInterface;

class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InventoryInterface::class, ShopInventory::class);
        $this->app->singleton(FulfillmentInterface::class, FulfillmentDispatcher::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'shop');
    }
}
