<?php

namespace App\Coupon\Domain\Coupon;

interface CouponRepository
{
    public function store(Coupon $coupon): Coupon;

    public function getById(int $id): Coupon;

    /**
     * @return list<Coupon>
     */
    public function list(): array;
}
