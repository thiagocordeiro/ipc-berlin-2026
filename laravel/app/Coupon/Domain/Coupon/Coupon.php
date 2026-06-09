<?php

namespace App\Coupon\Domain\Coupon;

use App\Shared\Domain\Entity;
use Carbon\CarbonImmutable;

readonly class Coupon extends Entity
{
    private function __construct(?int $id, public string $code, public CarbonImmutable $createdAt)
    {
        parent::__construct($id);
    }

    static public function create(string $code): self
    {
        if (!str_starts_with($code, 'IPC-')) {
            $code = 'IPC-' . $code;
        }

        return new self(
            id: null,
            code: $code,
            createdAt: CarbonImmutable::now(),
        );
    }

    static public function retrieve(
        ?int $id,
        string $code,
        CarbonImmutable $createdAt,
    ): self {
        return new self($id, $code, $createdAt);
    }
}
