<?php

namespace Tests\Feature\Shared;

use App\Campaign\Application\Share\ShareCouponWithCustomersCommand;
use App\Coupon\Domain\Coupon\CouponCreated;
use App\Shared\Domain\Event\Publisher;
use App\Shared\Domain\Event\Subscriber;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemoryPubSubTest extends TestCase
{
    private Publisher $publisher;
    private Subscriber $subscriber;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->publisher = $this->app->get(Publisher::class);
        $this->subscriber = $this->app->get(Subscriber::class);
    }

    #[Test]
    public function it_rehydrates_a_published_event_into_the_subscriber_command(): void
    {
        $received = null;
        $this->subscriber->subscribe(
            'coupon.created',
            function (ShareCouponWithCustomersCommand $command) use (&$received): void {
                $received = $command;
            },
        );

        $this->publisher->publish(new CouponCreated(id: 42, code: 'IPC-BERLIN-2026'));

        $this->assertInstanceOf(ShareCouponWithCustomersCommand::class, $received);
        $this->assertSame('IPC-BERLIN-2026', $received->code);
    }
}
