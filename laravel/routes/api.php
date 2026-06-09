<?php

use App\Campaign\Presenter\Http\ShareCouponWithCustomersController;
use App\Coupon\Presenter\Http\Coupon\Create\CreateCouponController;
use App\Coupon\Presenter\Http\Coupon\Get\GetCouponController;
use App\Coupon\Presenter\Http\Coupon\List\ListCouponsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/coupons')->group(function () {
    Route::post('/', CreateCouponController::class);
    Route::get('/', ListCouponsController::class);
    Route::get('/{code}', GetCouponController::class);
});

Route::prefix('/share')->group(function () {
    Route::post('/{code}', ShareCouponWithCustomersController::class);
});
