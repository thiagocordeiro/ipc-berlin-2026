<?php

namespace App\Coupon\Application\Coupon\Get;

use App\Coupon\Domain\Coupon\Coupon;
use App\Coupon\Domain\Coupon\CouponRepository;

readonly class GetCouponUseCase
{
    public function __construct(private CouponRepository $repository)
    {
    }

    public function handle(GetCouponQuery $query): Coupon
    {
        return $this->repository->getByCode($query->code);
    }
}
