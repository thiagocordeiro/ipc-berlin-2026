<?php

namespace App\Coupon\Presenter\Http\Coupon\Create;

use App\Coupon\Domain\Coupon\Coupon;
use Exception;

readonly class CreateCouponController
{
    public function __construct()
    {
    }

    public function __invoke(): Coupon
    {
        throw new Exception('Not implemented');
    }
}
