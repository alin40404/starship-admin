<?php

namespace Plugins\HookSample\Providers;

use Illuminate\Support\ServiceProvider;
use Starship\Core\Plugin\Plugin;

class HookSampleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        add_filter('starship.plugin.description', static function (?string $description, Plugin $plugin): ?string {
            if ($plugin->slug() !== 'mail-smtp') {
                return $description;
            }

            $suffix = ' · HookSample';
            if ($description === null || $description === '') {
                return trim($suffix);
            }

            if (str_contains($description, $suffix)) {
                return $description;
            }

            return $description.$suffix;
        });
    }
}
