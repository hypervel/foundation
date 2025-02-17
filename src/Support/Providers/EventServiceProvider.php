<?php

declare(strict_types=1);

namespace LaravelHyperf\Foundation\Support\Providers;

use LaravelHyperf\Support\ServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected array $listen = [];

    /**
     * The subscribers to register.
     */
    protected array $subscribe = [];

    public function register(): void
    {
        $provider = $this->app->get(ListenerProviderInterface::class);
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $instance = $this->app->get($listener);
                $provider->on($event, [$instance, 'handle']);
            }
        }

        $dispatcher = $this->app->get(EventDispatcherInterface::class);
        foreach ($this->subscribe as $subscriber) {
            $dispatcher->subscribe($subscriber);
        }
    }
}
