<?php

namespace App\Shared\Domain\Event;

interface Event
{
    public static function name(): string;
}
