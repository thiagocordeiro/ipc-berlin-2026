<?php

namespace App\Coupon\Application\Coupon\List;

use App\Coupon\Domain\Coupon\Coupon;
use App\Coupon\Domain\Coupon\CouponRepository;

readonly class ListCouponsUseCase
{
    public function __construct(private CouponRepository $couponRepository)
    {
    }

    /**
     * @return list<Coupon>
     */
    public function handle(): array
    {
        $coupons = $this->couponRepository->list();

        return [];
    }
}
