<?php

namespace App\Campaign\Presenter\Http;

use App\Campaign\Application\Share\ShareCouponWithCustomersCommand;
use App\Campaign\Application\Share\ShareCouponWithCustomersUseCase;
use Tcds\Io\Jackson\Laravel\Attributes\JacksonInject;

readonly class ShareCouponWithCustomersController
{
    public function __construct(
        private ShareCouponWithCustomersUseCase $useCase,
    ) {
    }

    public function __invoke(#[JacksonInject] ShareCouponWithCustomersCommand $command): void
    {
        $this->useCase->handle($command);
    }
}
