<?php

namespace App\Coupon\Infrastructure\Providers;

use App\Coupon\Domain\Coupon\CouponRepository;
use App\Coupon\Infrastructure\Memory\InMemoryCouponRepository;
use Illuminate\Support\ServiceProvider;

/**
 * @see CreateCouponController
 */
class CouponServiceProvider extends ServiceProvider
{
    public $bindings = [
        CouponRepository::class => InMemoryCouponRepository::class,
    ];
}
