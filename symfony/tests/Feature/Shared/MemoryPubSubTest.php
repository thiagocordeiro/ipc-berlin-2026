<?php

namespace App\Tests\Feature\Shared;

use App\Campaign\Application\Share\ShareCouponWithCustomersCommand;
use App\Coupon\Domain\Coupon\CouponCreated;
use App\Shared\Domain\Event\Publisher;
use App\Shared\Domain\Event\Subscriber;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MemoryPubSubTest extends KernelTestCase
{
    #[Test]
    public function it_rehydrates_a_published_event_into_the_subscriber_command(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var Subscriber $subscriber */
        $subscriber = $container->get(Subscriber::class);
        /** @var Publisher $publisher */
        $publisher = $container->get(Publisher::class);

        $received = null;

        // Subscribe by the event's topic name; the handler receives the Campaign command,
        // which the bus builds from the wire payload — no Coupon type referenced here.
        $subscriber->subscribe(
            'coupon.created',
            function (ShareCouponWithCustomersCommand $command) use (&$received): void {
                $received = $command;
            },
        );

        // Coupon publishes its own event; routing uses CouponCreated::name() === 'coupon.created'.
        $publisher->publish(new CouponCreated(id: 42, code: 'IPC-BERLIN-2026'));

        $this->assertInstanceOf(ShareCouponWithCustomersCommand::class, $received);
        $this->assertSame('IPC-BERLIN-2026', $received->code);
    }
}
