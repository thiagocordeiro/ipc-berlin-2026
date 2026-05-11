<?php

namespace App\Campaign\Presenter\Event;

use App\Campaign\Application\Share\ShareCouponWithCustomersCommand;
use App\Campaign\Application\Share\ShareCouponWithCustomersUseCase;

/**
 * Subscribed to the `coupon.created` topic. The bus rehydrates the wire payload
 * directly into this context's own command, which we hand over to the use case.
 */
readonly class ShareCouponWithCustomersEventListener
{
    public function __construct(
        private ShareCouponWithCustomersUseCase $useCase,
    ) {
    }

    public function __invoke(ShareCouponWithCustomersCommand $command): void
    {
        $this->useCase->handle($command);
    }
}
