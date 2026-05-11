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
                Hey there!
                <br><br>
                Enjoy the new coupon <strong>$command->code</strong> before IPC-BERLIN ends
                <br><br>
                <img src="https://media1.giphy.com/media/v1.Y2lkPTc5MGI3NjExaXcyM2g5eHV1cWp2ZG05dTd3aDhrdmszZHowdmo1cjNsOHoxZXpjZCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/GxIdtANXpn3qL1FG25/giphy.gif" alt="">
            HTML,
        );
    }
}
