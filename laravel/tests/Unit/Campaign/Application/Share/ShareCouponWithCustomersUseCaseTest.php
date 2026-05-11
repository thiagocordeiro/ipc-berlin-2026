<?php

namespace Tests\Unit\Campaign\Application\Share;

use App\Campaign\Application\Share\ShareCouponWithCustomersCommand;
use App\Campaign\Application\Share\ShareCouponWithCustomersUseCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeEmailSender;

class ShareCouponWithCustomersUseCaseTest extends TestCase
{
    #[Test]
    public function given_a_coupon_code_when_handling_then_emails_the_customers(): void
    {
        $sender = new FakeEmailSender();
        $useCase = new ShareCouponWithCustomersUseCase($sender);

        $useCase->handle(new ShareCouponWithCustomersCommand('IPC-BERLIN-2026'));

        $this->assertCount(1, $sender->sent);
        $this->assertSame('dev@tcds.io', $sender->sent[0]['to']);
        $this->assertSame('You IPC Coupon is here!', $sender->sent[0]['subject']);
        $this->assertStringContainsString('IPC-BERLIN-2026', $sender->sent[0]['message']);
    }
}
