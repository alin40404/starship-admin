<?php

namespace Starship\Core\Tests\Unit;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Event\EventDispatcherInterface;
use Starship\Core\Events\PluginDescriptionResolving;
use Starship\Core\Plugin\Plugin;
use Starship\Core\Tests\TestCase;

class EventDispatcherTest extends TestCase
{
    #[Test]
    public function container_resolves_event_dispatcher(): void
    {
        $this->assertInstanceOf(
            EventDispatcherInterface::class,
            $this->app->make(EventDispatcherInterface::class),
        );
    }

    #[Test]
    public function listeners_can_mutate_event_payload(): void
    {
        Event::listen(PluginDescriptionResolving::class, static function (PluginDescriptionResolving $event): void {
            $event->description = ($event->description ?? '').'-patched';
        });

        $plugin = new Plugin([
            'name' => 'Demo',
            'slug' => 'demo',
            'description' => 'base',
        ], sys_get_temp_dir());

        /** @var PluginDescriptionResolving $event */
        $event = $this->app->make(EventDispatcherInterface::class)->dispatch(
            new PluginDescriptionResolving($plugin, 'base'),
        );

        $this->assertSame('base-patched', $event->description);
    }
}
