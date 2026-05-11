<?php

namespace App\Coupon\Presenter\Http\Coupon\List;

use App\Coupon\Application\Coupon\List\ListCouponsUseCase;
use App\Coupon\Domain\Coupon\Coupon;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonResponse;

#[Route('/coupons', methods: ['GET'])]
readonly class ListCouponsController
{
    public function __construct(private ListCouponsUseCase $useCase)
    {
    }

    /**
     * @return list<Coupon>
     */
    #[JacksonResponse(status: Response::HTTP_OK)]
    public function __invoke(): array
    {
        return $this->useCase->handle();
    }
}
