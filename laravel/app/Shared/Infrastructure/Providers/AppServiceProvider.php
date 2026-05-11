<?php

namespace App\Shared\Infrastructure\Providers;

use App\Shared\Domain\Event\Publisher;
use App\Shared\Domain\Event\Subscriber;
use App\Shared\Infrastructure\MemoryPubSub;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        MemoryPubSub::class => MemoryPubSub::class,
        Publisher::class => MemoryPubSub::class,
        Subscriber::class => MemoryPubSub::class,
    ];
}
