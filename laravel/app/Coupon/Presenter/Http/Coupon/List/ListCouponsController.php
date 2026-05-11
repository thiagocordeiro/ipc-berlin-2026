<?php

namespace App\Coupon\Presenter\Http\Coupon\List;

use App\Coupon\Application\Coupon\List\ListCouponsUseCase;
use App\Coupon\Domain\Coupon\Coupon;
use Tcds\Io\Jackson\Laravel\Attributes\JacksonResponse;

readonly class ListCouponsController
{
    public function __construct(private ListCouponsUseCase $useCase)
    {
    }

    /**
     * @return list<Coupon>
     */
    #[JacksonResponse]
    public function __invoke(): array
    {
        return $this->useCase->handle();
    }
}
