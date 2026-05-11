<?php

namespace App\Campaign\Presenter\Event;

use App\Campaign\Application\Share\ShareCouponWithCustomersCommand;
use App\Campaign\Application\Share\ShareCouponWithCustomersUseCase;

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
