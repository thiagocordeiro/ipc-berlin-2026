<?php

namespace App\Coupon\Application\Coupon\Create;

use App\Coupon\Domain\Coupon\Coupon;
use App\Coupon\Domain\Coupon\CouponCreated;
use App\Coupon\Domain\Coupon\CouponRepository;
use App\Shared\Domain\Event\Publisher;

readonly class CreateCouponUseCase
{
    public function __construct(
        private CouponRepository $repository,
        private Publisher $publisher,
    ) {
    }

    public function handle(CreateCouponCommand $command): Coupon
    {
        $coupon = Coupon::create($command->code);
        $stored = $this->repository->store($coupon);
        $this->publisher->publish(new CouponCreated($stored->id(), $stored->code));

        return $stored;
    }
}
