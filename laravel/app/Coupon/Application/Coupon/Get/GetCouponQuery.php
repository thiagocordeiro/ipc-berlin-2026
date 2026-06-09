<?php

namespace App\Coupon\Application\Coupon\Get;

readonly class GetCouponQuery
{
    public function __construct(public string $code)
    {
    }
}
