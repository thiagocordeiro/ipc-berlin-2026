<?php

namespace App\Campaign\Application\Share;

readonly class ShareCouponWithCustomersCommand
{
    public function __construct(public string $code)
    {
    }
}
