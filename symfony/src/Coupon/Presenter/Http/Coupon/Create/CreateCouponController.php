<?php

namespace App\Coupon\Presenter\Http\Coupon\Create;

use App\Coupon\Domain\Coupon\Coupon;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tcds\Io\Jackson\Symfony\Attributes\JacksonResponse;

#[Route('/coupons', methods: ['POST'])]
readonly class CreateCouponController
{
    public function __construct()
    {
    }

    #[JacksonResponse(status: Response::HTTP_CREATED)]
    public function __invoke(): Coupon
    {
        throw new Exception('Not implemented');
    }
}
