<?php

namespace App\Providers;

use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        DB::prohibitDestructiveCommands(
            $this->app->isProduction()
        );

        SeedCommand::prohibit(
            $this->app->isProduction()
        );
    }
}
