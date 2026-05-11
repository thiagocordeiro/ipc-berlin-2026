<?php

namespace App\Coupon\Domain\Coupon;

use App\Shared\Domain\Entity;

readonly class Coupon extends Entity
{
    private function __construct(?int $id)
    {
        parent::__construct($id);
    }

    static public function create(): self
    {
        return new self(null);
    }

    static public function retrieve(int $id): self
    {
        return new self($id);
    }
}
