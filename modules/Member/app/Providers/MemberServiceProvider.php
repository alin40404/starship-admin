<?php

namespace Modules\Member\App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\Member\App\Models\Member;

class MemberServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureAuth();

        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'member');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'member');
    }

    protected function configureAuth(): void
    {
        Config::set('auth.guards.member', [
            'driver' => 'session',
            'provider' => 'members',
        ]);

        Config::set('auth.providers.members', [
            'driver' => 'eloquent',
            'model' => Member::class,
        ]);

        Config::set('auth.passwords.members', [
            'provider' => 'members',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ]);
    }
}
