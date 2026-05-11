<?php

namespace App\Tests\Unit\Shared;

use App\Campaign\Application\Share\ShareCouponWithCustomersCommand;
use App\Coupon\Domain\Coupon\CouponCreated;
use App\Shared\Infrastructure\MemoryPubSub;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stringable;
use Tcds\Io\Jackson\ObjectMapper;

class MemoryPubSubDispatchTest extends TestCase
{
    #[Test]
    public function it_serializes_and_deserializes_once_per_publish_for_subscribers_sharing_a_target(): void
    {
        $mapper = new class(['id' => 1, 'code' => 'IPC-BERLIN-2026'], new ShareCouponWithCustomersCommand('IPC-BERLIN-2026')) implements ObjectMapper {
            public int $reads = 0;
            public int $writes = 0;

            public function __construct(private array $payload, private object $decoded)
            {
            }

            public function readValueWith(string $type, mixed $value, array $with = [])
            {
                return $this->decoded;
            }

            public function readValue(string $type, mixed $value, array $path = []): mixed
            {
                ++$this->reads;

                return $this->decoded;
            }

            public function writeValue(mixed $value, ?string $type = null, array $path = []): mixed
            {
                ++$this->writes;

                return $this->payload;
            }
        };

        $bus = new MemoryPubSub($mapper, new NullLogger());

        $handled = 0;
        $handler = function (ShareCouponWithCustomersCommand $command) use (&$handled): void {
            ++$handled;
        };
        $bus->subscribe('coupon.created', $handler);
        $bus->subscribe('coupon.created', $handler);

        $bus->publish(new CouponCreated(id: 1, code: 'IPC-BERLIN-2026'));

        $this->assertSame(2, $handled, 'both subscribers handled the event');
        $this->assertSame(1, $mapper->writes, 'serialized once per publish');
        $this->assertSame(1, $mapper->reads, 'deserialized once per publish, reused across subscribers of the same target');
    }

    #[Test]
    public function it_defers_subscriber_reflection_until_the_topic_is_published(): void
    {
        $mapper = new class implements ObjectMapper {
            public function readValueWith(string $type, mixed $value, array $with = [])
            {
                return null;
            }

            public function readValue(string $type, mixed $value, array $path = []): mixed
            {
                return null;
            }

            public function writeValue(mixed $value, ?string $type = null, array $path = []): mixed
            {
                return [];
            }
        };

        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $errors = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                if ($level === LogLevel::ERROR) {
                    $this->errors[] = (string) $message;
                }
            }
        };

        $bus = new MemoryPubSub($mapper, $logger);

        // A structurally invalid subscriber (scalar parameter) is accepted without reflection...
        $bus->subscribe('coupon.created', fn (string $notAnEvent) => null);
        $this->assertSame([], $logger->errors, 'no reflection — and so no error — at wiring time');

        // ...the failure only surfaces (caught + logged) once the topic actually fires.
        $bus->publish(new CouponCreated(id: 1, code: 'IPC-BERLIN-2026'));
        $this->assertCount(1, $logger->errors, 'reflection happened lazily, at publish time');
    }
}
