<?php

namespace App\Coupon\Domain\Coupon;

use App\Shared\Domain\Entity;

readonly class Coupon extends Entity
{
    private function __construct(?int $id)
    {
        parent::__construct($id);
    }

    public static function create(): self
    {
        return new self(null);
    }

    public static function retrieve(int $id): self
    {
        return new self($id);
    }
}
