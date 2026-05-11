<?php

namespace App\Coupon\Infrastructure\Memory;

use App\Coupon\Domain\Coupon\Coupon;
use App\Coupon\Domain\Coupon\CouponRepository;
use Exception;

class InMemoryCouponRepository implements CouponRepository
{
    public function __construct(private array $coupons = [], private int $lastInsertedId = 0)
    {
    }

    public function store(Coupon $coupon): Coupon
    {
        $id = ++$this->lastInsertedId;

        $stored = Coupon::retrieve($id, $coupon->code, $coupon->createdAt);

        return $this->coupons[$id] = $stored;
    }

    public function getById(int $id): Coupon
    {
        return $this->coupons[$id] ?? throw new Exception('Coupon not found');
    }

    public function list(): array
    {
        return $this->coupons;
    }
}
