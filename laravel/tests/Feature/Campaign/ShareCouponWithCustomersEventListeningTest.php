<?php

namespace Tests\Feature\Campaign;

use App\Coupon\Domain\Coupon\CouponCreated;
use App\Shared\Domain\Email\EmailSender;
use App\Shared\Domain\Event\Publisher;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeEmailSender;
use Tests\TestCase;

class ShareCouponWithCustomersEventListeningTest extends TestCase
{
    #[Test]
    public function when_a_coupon_is_created_then_the_customers_are_emailed_the_code(): void
    {
        /** @var FakeEmailSender $sender */
        $sender = $this->app->get(EmailSender::class);

        // Publish the Coupon context's event; the Campaign listener is wired to react to it.
        $this->app->get(Publisher::class)->publish(
            new CouponCreated(id: 1, code: 'IPC-BERLIN-2026'),
        );

        $this->assertCount(1, $sender->sent);
        $this->assertSame('dev@tcds.io', $sender->sent[0]['to']);
        $this->assertSame('You IPC Coupon is here!', $sender->sent[0]['subject']);
        $this->assertStringContainsString('IPC-BERLIN-2026', $sender->sent[0]['message']);
    }
}
