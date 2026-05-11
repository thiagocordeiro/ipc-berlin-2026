<?php

namespace App\Shared\Domain\Event;

interface Event
{
    /**
     * Logical topic name — the published language shared across bounded contexts.
     * Contexts agree on this string, never on each other's classes.
     */
    public static function name(): string;
}
