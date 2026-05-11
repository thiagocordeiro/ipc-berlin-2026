<?php

use App\Campaign\Infrastructure\Providers\CampaignServiceProvider;
use App\Coupon\Infrastructure\Providers\CouponServiceProvider;
use App\Shared\Infrastructure\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CouponServiceProvider::class,
    CampaignServiceProvider::class,
];
