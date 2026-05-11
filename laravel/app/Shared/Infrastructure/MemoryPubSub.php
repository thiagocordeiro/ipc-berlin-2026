<?php

namespace App\Shared\Infrastructure;

use App\Shared\Domain\Event\Event;
use App\Shared\Domain\Event\Publisher;
use App\Shared\Domain\Event\Subscriber;
use Closure;
use InvalidArgumentException;
use Override;
use Psr\Log\LoggerInterface;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Tcds\Io\Jackson\ObjectMapper;
use Throwable;

class MemoryPubSub implements Publisher, Subscriber
{
    /** @var array<string, list<array{handler: callable, target: ?class-string}>> */
    private array $subscribers = [];

    public function __construct(
        private readonly ObjectMapper $mapper,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Override]
    public function publish(Event $event): void
    {
        $name = $event::name();
        $subscribers = $this->subscribers[$name] ?? [];

        if ($subscribers === []) {
            return;
        }

        // Serialize once per publish...
        $payload = $this->mapper->writeValue($event);

        // ...and deserialize once per target type, reused across subscribers of this publish.
        $inputs = [];

        foreach ($subscribers as $index => $subscriber) {
            try {
                // Reflect each subscriber's target type once — lazily, only when its topic
                // actually fires — and cache it for every later publish.
                $target = $this->subscribers[$name][$index]['target']
                    ??= $this->targetType($subscriber['handler']);

                $input = $inputs[$target] ??= $this->mapper->readValue($target, $payload);

                ($subscriber['handler'])($input);
            } catch (Throwable $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        }
    }

    #[Override]
    public function subscribe(string $name, callable $subscriber): self
    {
        // No reflection at wiring time — the target type is resolved lazily on first publish.
        $this->subscribers[$name][] = ['handler' => $subscriber, 'target' => null];

        return $this;
    }

    /**
     * @return class-string
     */
    private function targetType(callable $subscriber): string
    {
        $reflection = match (true) {
            is_array($subscriber) => new ReflectionMethod($subscriber[0], $subscriber[1]),
            is_object($subscriber) && !$subscriber instanceof Closure => new ReflectionMethod($subscriber, '__invoke'),
            default => new ReflectionFunction($subscriber),
        };

        $type = $reflection->getParameters()[0]->getType() ?? null;

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new InvalidArgumentException('An event subscriber must type-hint a single class parameter to receive');
        }

        /** @var class-string */
        return $type->getName();
    }
}
