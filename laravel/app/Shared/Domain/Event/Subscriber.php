<?php

namespace App\Shared\Domain\Event;

interface Subscriber
{
    /**
     * @param callable(object $input): void $subscriber
     */
    public function subscribe(string $name, callable $subscriber): self;
}
