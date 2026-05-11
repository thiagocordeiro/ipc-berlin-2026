<?php

namespace App\Shared\Domain;

use Exception;

abstract readonly class Entity
{
    public function __construct(private ?int $id)
    {
    }

    public function id(): int
    {
        return $this->id ?? throw new Exception('Not stored');
    }
}
