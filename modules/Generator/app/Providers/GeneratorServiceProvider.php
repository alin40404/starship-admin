<?php

namespace Modules\Generator\App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Generator\App\Console\Commands\GeneratorCrudCommand;
use Modules\Generator\App\Console\Commands\GeneratorImportCommand;
use Modules\Generator\App\Console\Commands\GeneratorInspectCommand;
use Modules\Generator\App\Console\Commands\GeneratorTablesCommand;
use Modules\Generator\App\Events\GeneratorCodeGenerated;
use Modules\Generator\App\Listeners\LogGeneratorCodeGenerated;

class GeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'generator');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GeneratorTablesCommand::class,
                GeneratorInspectCommand::class,
                GeneratorImportCommand::class,
                GeneratorCrudCommand::class,
            ]);
        }

        Event::listen(GeneratorCodeGenerated::class, LogGeneratorCodeGenerated::class);
    }
}
