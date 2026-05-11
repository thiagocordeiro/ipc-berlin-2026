<?php

namespace App\Coupon\Domain\Coupon;

use App\Shared\Domain\Event\Event;

readonly class CouponCreated implements Event
{
    public function __construct(public int $id, public string $code)
    {
    }

    public static function name(): string
    {
        return 'coupon.created';
    }
}
