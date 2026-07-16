<?php

namespace Modules\Member\App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\Member\App\Models\Member;
use Modules\Member\App\Support\MemberProvider;
use Modules\System\App\Support\GuestRedirect;
use Starship\Core\Contracts\Member\MemberProviderInterface;

class MemberServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MemberProviderInterface::class, MemberProvider::class);
    }

    public function boot(): void
    {
        $this->configureAuth();

        GuestRedirect::using(static function (Request $request): string|null|false {
            if ($request->is('member', 'member/*')) {
                return route('member.login');
            }

            return false;
        });

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
