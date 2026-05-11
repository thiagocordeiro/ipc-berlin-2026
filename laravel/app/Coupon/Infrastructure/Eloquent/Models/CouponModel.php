<?php

namespace App\Coupon\Infrastructure\Eloquent\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property CarbonImmutable $created_at
 */
class CouponModel extends Model
{
    protected $table = 'coupons';

    protected $casts = [
        'created_at' => 'datetime_immutable',
    ];
}
