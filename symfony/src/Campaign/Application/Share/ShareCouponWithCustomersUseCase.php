<?php

namespace App\Campaign\Application\Share;

use App\Shared\Domain\Email\EmailSender;

readonly class ShareCouponWithCustomersUseCase
{
    public function __construct(private EmailSender $sender)
    {
    }

    public function handle(ShareCouponWithCustomersCommand $command): void
    {
        $this->sender->send(
            to: 'dev@tcds.io',
            subject: 'You IPC Coupon is here!',
            message: <<<HTML
                "Hey there!
                <br><br>
                Enjoy the new coupon <strong>$command->code</strong> before IPC-BERLIN ends"
            HTML,
        );
    }
}
