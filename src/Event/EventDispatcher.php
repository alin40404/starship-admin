<?php

namespace Starship\Core\Event;

use Illuminate\Contracts\Events\Dispatcher;

final class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        protected Dispatcher $dispatcher,
    ) {}

    public function dispatch(object $event): object
    {
        $this->dispatcher->dispatch($event);

        return $event;
    }
}
