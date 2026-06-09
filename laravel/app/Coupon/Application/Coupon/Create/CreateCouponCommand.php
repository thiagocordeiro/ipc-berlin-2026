<?php

namespace App\Coupon\Application\Coupon\Create;

readonly class CreateCouponCommand
{
    public function __construct(public string $code)
    {
    }
}
