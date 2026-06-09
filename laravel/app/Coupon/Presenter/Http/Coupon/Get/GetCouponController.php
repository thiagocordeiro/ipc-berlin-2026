<?php

namespace App\Coupon\Presenter\Http\Coupon\Get;

use App\Coupon\Application\Coupon\Get\GetCouponQuery;
use App\Coupon\Application\Coupon\Get\GetCouponUseCase;
use App\Coupon\Domain\Coupon\Coupon;
use Tcds\Io\Jackson\Laravel\Attributes\JacksonInject;
use Tcds\Io\Jackson\Laravel\Attributes\JacksonResponse;

readonly class GetCouponController
{
    public function __construct(private GetCouponUseCase $useCase)
    {
    }

    #[JacksonResponse]
    public function __invoke(#[JacksonInject] GetCouponQuery $query): Coupon
    {
        return $this->useCase->handle($query);
    }
}
