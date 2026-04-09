<?php

namespace App\Providers;

use App\Support\Module\ModuleManager;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $manager = new ModuleManager(base_path('../../modules'));

        foreach ($manager->enabled() as $module) {
            foreach ($module->providers() as $provider) {
                if (class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }

    public function boot(): void
    {
        //
    }
}
