<?php

namespace App\Shared\Domain\Event;

interface Publisher
{
    public function publish(Event $event): void;
}
