<?php

namespace App\Shared\Domain\Event;

interface Subscriber
{
    /**
     * Subscribe to an event by its topic name (see {@see Event::name()}).
     * The handler's parameter type is what the payload is rehydrated into —
     * a command or input local to the subscribing context, never the publisher's event.
     *
     * @param callable(object $input): void $subscriber
     */
    public function subscribe(string $name, callable $subscriber): self;
}
