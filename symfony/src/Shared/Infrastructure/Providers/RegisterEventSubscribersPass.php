<?php

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Infrastructure\MemoryPubSub;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires every service tagged `app.event_subscriber` onto the in-memory bus.
 *
 * This is the Symfony equivalent of Laravel's per-module `ServiceProvider::boot()`:
 * each bounded context declares its own subscriptions (via the tag, in its own
 * services.yaml), and Shared collects them here without ever knowing the contexts exist.
 */
final class RegisterEventSubscribersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(MemoryPubSub::class)) {
            return;
        }

        $bus = $container->getDefinition(MemoryPubSub::class);

        foreach ($container->findTaggedServiceIds('app.event_subscriber') as $id => $tags) {
            foreach ($tags as $tag) {
                $bus->addMethodCall('subscribe', [$tag['event'], new Reference($id)]);
            }
        }
    }
}
