<?php

namespace App\Coupon\Presenter\Http\Coupon\Create;

use App\Coupon\Application\Coupon\Create\CreateCouponCommand;
use App\Coupon\Application\Coupon\Create\CreateCouponUseCase;
use App\Coupon\Domain\Coupon\Coupon;
use Tcds\Io\Jackson\Laravel\Attributes\JacksonInject;
use Tcds\Io\Jackson\Laravel\Attributes\JacksonResponse;

readonly class CreateCouponController
{
    public function __construct(private CreateCouponUseCase $useCase)
    {
    }

    #[JacksonResponse(201)]
    public function __invoke(#[JacksonInject] CreateCouponCommand $command): Coupon
    {
        return $this->useCase->handle($command);
    }
}
