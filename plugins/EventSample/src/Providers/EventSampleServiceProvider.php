<?php

namespace Plugins\EventSample\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Starship\Core\Events\PluginDescriptionResolving;

class EventSampleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(PluginDescriptionResolving::class, static function (PluginDescriptionResolving $event): void {
            if ($event->plugin->slug() !== 'mail-smtp') {
                return;
            }

            $suffix = ' | EventSample';
            $description = $event->description;
            if ($description === null || $description === '') {
                $event->description = trim($suffix);

                return;
            }

            if (str_contains($description, $suffix)) {
                return;
            }

            $event->description = $description.$suffix;
        });
    }
}
